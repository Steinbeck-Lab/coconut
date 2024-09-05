# Download

COCONUT provides users with various download options listed below, offering a convenient means to obtain chemical structures of natural products in a widely accepted and machine-readable format.
* Postgres dump. 
* CSV.
* [SDF](https://en.wikipedia.org/wiki/Chemical_table_file#SDF) format.

![Database Downloads](/database-download.png)


These downloads are monthly and would contain all the changes made cumulatively to the database during the previous month.

::: warning
Please note that the COCONUT dataset is subject to certain terms of use and licensing restrictions. Make sure to review and comply with the respective terms and conditions associated with the dataset.
:::

## Download the COCONUT dataset as a Postgres dump

This functionality allows you to obtain the comprehensive COCONUT dataset in the form of a Postgres dump file. Once you have downloaded the Postgres dump file, you can import it into your local Postgres database management system by following the below instruction, which will allow you to explore, query, and analyze the COCONUT dataset using SQL statements within your own environment.

::: info
The Postgres dump exclusively comprises data only from the following tables: molecules, properties, and citations.
:::
<!-- 
### Instruction to restore

To restore the database using the dump file, follow these instructions:

* Make sure the application is running successfully in the docker containers.

* Unzip the downloaded dump file.

* To import, run the below command by replacing the database name and username with yours and enter the password when prompted.

```bash
docker exec -i <your-container-id> /bin/bash -c "PGPASSWORD=<your-posgressDB-password> psql --username <your-postgress-username> <your-DB-name>" < <path-to-the-sql-dump>
```

## Download Natural Products Structures in Canonical and Absolute SMILES format

The "Download Natural Products Structures in SMILES format" API provides a convenient way to obtain the chemical structures of natural products in the Cannonical [Simplified Molecular Input Line Entry System (SMILES)](https://en.wikipedia.org/wiki/Simplified_molecular-input_line-entry_system) and Absolute SMILES format. This format represents molecular structures using a string of ASCII characters, allowing for easy storage, sharing, and processing of chemical information.

## Download Natural Products Structures in SDF format

This functionality provides a convenient way to access the chemical structures of natural products in the [Structure-Data File (SDF)](https://en.wikipedia.org/wiki/Chemical_table_file#SDF) format. SDF is a widely used file format for representing molecular structures and associated data, making it suitable for various cheminformatics applications. -->