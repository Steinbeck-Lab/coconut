"""
Publish monthly COCONUT exports to Zenodo as a new dataset version.

Configuration (via .env or environment):
    ZENODO_ENABLED              - set to true to run after monthly export
    ZENODO_ACCESS_TOKEN         - personal access token (deposit:write + deposit:actions)
    ZENODO_LATEST_DEPOSITION_ID - record id of the latest published Zenodo version
    ZENODO_API_URL              - default https://zenodo.org/api
    ZENODO_AUTO_PUBLISH         - set to true to publish immediately (default: draft only)
    ZENODO_DRY_RUN              - set to true to validate without API calls
    ZENODO_RELEASE_NOTES        - optional HTML/text prepended to the record description
"""

from __future__ import annotations

import argparse
import os
import re
import sys
import zipfile
from datetime import datetime
from typing import Any

import requests
from tqdm import tqdm


DEFAULT_API_URL = "https://zenodo.org/api"


def load_env(file_path: str) -> dict[str, str]:
    env_vars: dict[str, str] = {}
    if os.path.exists(file_path):
        with open(file_path) as f:
            for line in f:
                line = line.strip()
                if line and not line.startswith("#") and "=" in line:
                    key, value = line.split("=", 1)
                    env_vars[key] = value.strip().strip('"').strip("'")
    return env_vars


def env_flag(env_vars: dict[str, str], key: str, default: bool = False) -> bool:
    value = env_vars.get(key, str(default)).lower()
    return value in ("1", "true", "yes", "on")


def get_zenodo_config(env_vars: dict[str, str]) -> dict[str, Any] | None:
    if not env_flag(env_vars, "ZENODO_ENABLED"):
        return None

    token = env_vars.get("ZENODO_ACCESS_TOKEN", "").strip()
    deposition_id = env_vars.get("ZENODO_LATEST_DEPOSITION_ID", "").strip()

    if not token:
        raise ValueError("ZENODO_ENABLED is true but ZENODO_ACCESS_TOKEN is not set")
    if not deposition_id:
        raise ValueError("ZENODO_ENABLED is true but ZENODO_LATEST_DEPOSITION_ID is not set")

    return {
        "token": token,
        "deposition_id": int(deposition_id),
        "api_url": env_vars.get("ZENODO_API_URL", DEFAULT_API_URL).rstrip("/"),
        "auto_publish": env_flag(env_vars, "ZENODO_AUTO_PUBLISH"),
        "dry_run": env_flag(env_vars, "ZENODO_DRY_RUN"),
        "release_notes": env_vars.get("ZENODO_RELEASE_NOTES", "").strip(),
        "concept_doi": env_vars.get("ZENODO_CONCEPT_DOI", "").strip(),
    }


def zenodo_file_mapping(
    backup_path: str,
    month_year: str,
    full_dump_path: str | None,
) -> dict[str, str]:
    """Map Zenodo upload names to local export files."""
    mapping = {
        f"coconut-{month_year}.csv.zip": os.path.join(
            backup_path, f"coconut_csv_lite-{month_year}.zip"
        ),
        f"coconut_complete-{month_year}.csv.zip": os.path.join(
            backup_path, f"coconut_csv-{month_year}.zip"
        ),
        f"coconut-{month_year}.sdf.zip": os.path.join(
            backup_path, f"coconut_sdf_2d_lite-{month_year}.zip"
        ),
        f"coconut_complete-{month_year}.sdf.zip": os.path.join(
            backup_path, f"coconut_sdf_2d-{month_year}.zip"
        ),
    }

    sql_zip = os.path.join(backup_path, f"coconut-dump-{month_year}.sql.zip")
    mapping[f"coconut-dump-{month_year}.sql.zip"] = sql_zip

    return mapping


def ensure_sql_zip(full_dump_path: str, sql_zip_path: str) -> str:
    """Create the SQL zip for Zenodo if it does not exist yet."""
    if os.path.exists(sql_zip_path):
        return sql_zip_path

    if not full_dump_path or not os.path.exists(full_dump_path):
        raise FileNotFoundError(
            f"SQL dump not found for Zenodo upload: {full_dump_path or '(not provided)'}"
        )

    print(f"Creating {sql_zip_path} from {full_dump_path} ...")
    with zipfile.ZipFile(sql_zip_path, "w", zipfile.ZIP_DEFLATED) as zipf:
        zipf.write(full_dump_path, os.path.basename(full_dump_path))

    print(f"Created SQL zip ({os.path.getsize(sql_zip_path) / (1024 ** 3):.2f} GB)")
    return sql_zip_path


def validate_local_files(
    file_mapping: dict[str, str],
    full_dump_path: str | None,
) -> list[str]:
    missing: list[str] = []

    for zenodo_name, local_path in file_mapping.items():
        if zenodo_name.endswith(".sql.zip"):
            if not os.path.exists(local_path):
                try:
                    ensure_sql_zip(full_dump_path or "", local_path)
                except FileNotFoundError:
                    missing.append(zenodo_name)
            continue

        if not os.path.exists(local_path):
            missing.append(f"{zenodo_name} -> {local_path}")

    return missing


def fetch_database_stats(db_params: dict[str, str] | None) -> dict[str, int] | None:
    if not db_params:
        return None

    queries = {
        "molecules": "SELECT COUNT(*) FROM molecules",
        "collections": "SELECT COUNT(*) FROM collections",
        "organisms": "SELECT COUNT(DISTINCT organism_id) FROM molecule_organism",
        "citations": "SELECT COUNT(*) FROM citations",
    }

    try:
        import psycopg2
    except ImportError:
        print("Warning: psycopg2 not installed; skipping database stats for Zenodo metadata.")
        return None

    conn = None
    try:
        conn = psycopg2.connect(
            dbname=db_params.get("dbname", "coconut"),
            user=db_params.get("user"),
            password=db_params.get("password"),
            host=db_params.get("host", "127.0.0.1"),
            port=db_params.get("port", "5432"),
        )
        stats: dict[str, int] = {}
        with conn.cursor() as cursor:
            for key, query in queries.items():
                cursor.execute(query)
                stats[key] = int(cursor.fetchone()[0])
        return stats
    except Exception as exc:
        print(f"Warning: could not fetch database stats for Zenodo metadata: {exc}")
        return None
    finally:
        if conn:
            conn.close()


def update_description_stats(description: str, stats: dict[str, int]) -> str:
    """Update the summary statistics row in the inherited Zenodo HTML description."""
    values = [
        f"{stats['molecules']:,}",
        f"{stats['collections']:,}",
        f"{stats['organisms']:,}",
        f"{stats['citations']:,}",
    ]

    pattern = (
        r"(<tr>\s*<td>)(\d[\d,]*)(</td>\s*<td>)(\d[\d,]*)(</td>\s*<td>)(\d[\d,]*)"
        r"(</td>\s*<td>\s*(?:<p>)?)(\d[\d,]*)"
    )
    replacement = rf"\g<1>{values[0]}\g<3>{values[1]}\g<5>{values[2]}\g<7>{values[3]}"

    updated, count = re.subn(pattern, replacement, description, count=1)
    return updated if count else description


def build_metadata_update(
    draft: dict[str, Any],
    month_year: str,
    release_notes: str,
    stats: dict[str, int] | None,
) -> dict[str, Any]:
    metadata = dict(draft.get("metadata", {}))
    metadata["publication_date"] = datetime.now().strftime("%Y-%m-%d")

    description = metadata.get("description", "")

    if stats:
        description = update_description_stats(description, stats)

    if release_notes:
        notes_block = (
            f"<h1>COCONUT Release Notes ({month_year}):</h1>\n"
            f"<ul><li>{release_notes}</li></ul>\n"
        )
        description = notes_block + description
    elif stats:
        description = (
            f"<h1>COCONUT Release Notes ({month_year}):</h1>\n"
            f"<ul><li>Monthly database export.</li></ul>\n"
        ) + description

    metadata["description"] = description

    return {"metadata": metadata}


class ZenodoPublisher:
    def __init__(self, api_url: str, token: str, dry_run: bool = False):
        self.api_url = api_url.rstrip("/")
        self.headers = {"Authorization": f"Bearer {token}"}
        self.dry_run = dry_run

    def _request(self, method: str, url: str, **kwargs) -> requests.Response:
        if self.dry_run:
            print(f"[dry-run] {method.upper()} {url}")
            return requests.Response()

        response = requests.request(method, url, headers=self.headers, timeout=300, **kwargs)
        response.raise_for_status()
        return response

    def create_new_version_draft(self, latest_deposition_id: int) -> dict[str, Any]:
        url = f"{self.api_url}/deposit/depositions/{latest_deposition_id}/actions/newversion"
        if self.dry_run:
            print(f"[dry-run] Would create new version from deposition {latest_deposition_id}")
            return {
                "id": latest_deposition_id + 1,
                "links": {
                    "latest_draft": f"{self.api_url}/deposit/depositions/{latest_deposition_id + 1}",
                    "bucket": f"{self.api_url}/files/dry-run-bucket",
                },
                "metadata": {},
                "files": [],
            }

        response = self._request("POST", url)
        payload = response.json()
        draft_url = payload["links"]["latest_draft"]
        draft_response = self._request("GET", draft_url)
        return draft_response.json()

    def clear_draft_files(self, draft: dict[str, Any]) -> None:
        for file_info in draft.get("files", []):
            file_url = file_info["links"]["self"]
            if self.dry_run:
                print(f"[dry-run] Would delete {file_info.get('filename', file_url)}")
                continue
            self._request("DELETE", file_url)

    def upload_file(self, bucket_url: str, zenodo_name: str, local_path: str) -> None:
        file_size = os.path.getsize(local_path)
        upload_url = f"{bucket_url}/{zenodo_name}"

        if self.dry_run:
            print(f"[dry-run] Would upload {local_path} -> {zenodo_name} ({file_size} bytes)")
            return

        with open(local_path, "rb") as file_handle:
            with tqdm.wrapattr(
                file_handle,
                "read",
                total=file_size,
                unit="B",
                unit_scale=True,
                desc=zenodo_name,
            ) as wrapped:
                response = requests.put(
                    upload_url,
                    data=wrapped,
                    headers=self.headers,
                )
                response.raise_for_status()

    def update_metadata(self, draft_url: str, metadata: dict[str, Any]) -> dict[str, Any]:
        if self.dry_run:
            print(f"[dry-run] Would update metadata on {draft_url}")
            return metadata

        response = self._request("PUT", draft_url, json=metadata)
        return response.json()

    def publish(self, draft_id: int) -> dict[str, Any]:
        url = f"{self.api_url}/deposit/depositions/{draft_id}/actions/publish"
        if self.dry_run:
            print(f"[dry-run] Would publish deposition {draft_id}")
            return {"id": draft_id, "doi": "10.5281/zenodo.dry-run"}

        response = self._request("POST", url)
        return response.json()


def publish_monthly_release(
    env_vars: dict[str, str],
    backup_path: str,
    month_year: str,
    full_dump_path: str | None = None,
    db_params: dict[str, str] | None = None,
    dry_run: bool | None = None,
) -> dict[str, Any] | None:
    """
    Publish the monthly export to Zenodo. Returns publish result dict or None if skipped.
    """
    try:
        config = get_zenodo_config(env_vars)
    except ValueError as exc:
        print(f"Zenodo publish skipped: {exc}")
        return None

    if config is None:
        print("Zenodo publish skipped (ZENODO_ENABLED is not true).")
        return None

    if dry_run is not None:
        config["dry_run"] = dry_run

    file_mapping = zenodo_file_mapping(backup_path, month_year, full_dump_path)
    missing = validate_local_files(file_mapping, full_dump_path)
    if missing:
        raise FileNotFoundError(
            "Missing export files required for Zenodo upload:\n  - "
            + "\n  - ".join(missing)
        )

    publisher = ZenodoPublisher(
        api_url=config["api_url"],
        token=config["token"],
        dry_run=config["dry_run"],
    )

    print(
        f"Preparing Zenodo release for {month_year} "
        f"(base deposition {config['deposition_id']})..."
    )

    draft = publisher.create_new_version_draft(config["deposition_id"])
    draft_id = draft["id"]
    draft_url = f"{config['api_url']}/deposit/depositions/{draft_id}"
    bucket_url = draft["links"]["bucket"]

    publisher.clear_draft_files(draft)

    for zenodo_name, local_path in file_mapping.items():
        if zenodo_name.endswith(".sql.zip"):
            ensure_sql_zip(full_dump_path or "", local_path)
        publisher.upload_file(bucket_url, zenodo_name, local_path)

    stats = fetch_database_stats(db_params)
    metadata_update = build_metadata_update(
        draft,
        month_year,
        config["release_notes"],
        stats,
    )
    publisher.update_metadata(draft_url, metadata_update)

    result: dict[str, Any] = {
        "draft_id": draft_id,
        "draft_url": f"{config['api_url'].replace('/api', '')}/deposit/{draft_id}",
        "record_url": None,
        "doi": None,
        "published": False,
    }

    if config["auto_publish"]:
        published = publisher.publish(draft_id)
        record_id = published.get("record_id", published.get("id", draft_id))
        result.update(
            {
                "published": True,
                "record_id": record_id,
                "record_url": f"{config['api_url'].replace('/api', '')}/records/{record_id}",
                "doi": published.get("doi"),
            }
        )
        print(f"Published Zenodo record: {result['record_url']}")
        if result["doi"]:
            print(f"Version DOI: {result['doi']}")
        print(f"Update ZENODO_LATEST_DEPOSITION_ID={record_id} before the next release.")
    else:
        print(f"Zenodo draft created (not published): {result['draft_url']}")
        print("Review the draft in Zenodo, then publish manually or set ZENODO_AUTO_PUBLISH=true.")

    if config["concept_doi"]:
        print(f"Concept DOI: {config['concept_doi']}")

    return result


def parse_arguments() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description="Publish monthly COCONUT exports to Zenodo")
    parser.add_argument("--env-file", default="/app/coconut/.env", help="Path to the .env file")
    parser.add_argument("--backup-path", help="Local export directory (default: YYYY-MM/)")
    parser.add_argument("--month-year", help="Export month label MM-YYYY (default: current month)")
    parser.add_argument(
        "--full-dump-path",
        help="Path to the full SQL dump used to build coconut-dump-MM-YYYY.sql.zip",
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Validate files and show planned API calls without uploading",
    )
    parser.add_argument(
        "--publish",
        action="store_true",
        help="Publish immediately (overrides ZENODO_AUTO_PUBLISH for this run)",
    )
    return parser.parse_args()


def main() -> int:
    args = parse_arguments()
    env_vars = load_env(args.env_file)

    current = datetime.now()
    month_year = args.month_year or current.strftime("%m-%Y")
    backup_path = args.backup_path or os.path.join(os.getcwd(), f"{current.year}-{current.month:02d}")

    if args.publish:
        env_vars["ZENODO_AUTO_PUBLISH"] = "true"

    db_params = {
        "dbname": env_vars.get("DB_NAME", env_vars.get("DB_DATABASE", "coconut")),
        "user": env_vars.get("DB_USER", env_vars.get("DB_USERNAME")),
        "password": env_vars.get("DB_PASSWORD"),
        "host": "127.0.0.1",
        "port": env_vars.get("DB_PORT", "5432"),
    }

    try:
        publish_monthly_release(
            env_vars=env_vars,
            backup_path=backup_path,
            month_year=month_year,
            full_dump_path=args.full_dump_path,
            db_params=db_params,
            dry_run=args.dry_run,
        )
    except (FileNotFoundError, requests.HTTPError, ValueError) as exc:
        print(f"Zenodo publish failed: {exc}", file=sys.stderr)
        return 1

    return 0


if __name__ == "__main__":
    sys.exit(main())
