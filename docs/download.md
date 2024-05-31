# Download

Coconut online provides users with various download options listed below, offering a convenient means to obtain chemical structures of natural products in a widely accepted and machine-readable format.
* Download the COCONUT dataset as a Postgres dump. 
* Download Natural Products Structures in Canonical and Absolute [SMILES](https://en.wikipedia.org/wiki/Simplified_molecular-input_line-entry_system) format.
* Download Natural Products Structures in [SDF](https://en.wikipedia.org/wiki/Chemical_table_file#SDF) format.

At the end of each month, precisely at 00:00 CET, a snapshot of the Coconut data is taken and archived in an S3 storage bucket. To obtain the dump file of the most recent snapshot through UI, navigate to the left panel of your dashboard and locate the [Download](https://coconut.naturalproducts.net/download) button. Click on the [Download](https://coconut.naturalproducts.net/download) with option as desired and this will initiate the download of the data file containing the latest snapshot.

To access data through the API, refer to the [API](/download-api) or [Swagger](https://dev.coconut.naturalproducts.net/api/documentation) documentation for instructions on downloading the data.

::: warning
Please note that the COCONUT dataset is subject to certain terms of use and licensing restrictions. Make sure to review and comply with the respective terms and conditions associated with the dataset.
:::

## Download the COCONUT dataset as a Postgres dump

This functionality allows you to obtain the comprehensive COCONUT dataset in the form of a Postgres dump file. Once you have downloaded the [Postgres dump](https://www.postgresql.org/docs/current/app-pgdump.html#:~:text=pg_dump%20is%20a%20utility%20for,only%20dumps%20a%20single%20database.) file, you can import it into your local Postgres database management system by following the below instruction, which will allow you to explore, query, and analyze the COCONUT dataset using SQL statements within your own environment.

::: info
The Postgres dump exclusively comprises data only from the following tables: molecules, properties, and citations.
:::

### Instruction to restore

To restore the database using the dump file, follow these instructions:

* Make sure that Postgres (version 14.0 or higher) is up and running on your system.

* Unzip the downloaded dump file.

* To import, run the below command by replacing the database name and username with yours and enter the password when prompted.

```bash
psql -h 127.0.0.1 -p 5432 -d < database name > -U < username > -W < postgresql-coconut.sql
```

## Download Natural Products Structures in Canonical and Absolute SMILES format

The "Download Natural Products Structures in SMILES format" API provides a convenient way to obtain the chemical structures of natural products in the Cannonical [Simplified Molecular Input Line Entry System (SMILES)](https://en.wikipedia.org/wiki/Simplified_molecular-input_line-entry_system) and Absolute SMILES format. This format represents molecular structures using a string of ASCII characters, allowing for easy storage, sharing, and processing of chemical information.

## Download Natural Products Structures in SDF format

This functionality provides a convenient way to access the chemical structures of natural products in the [Structure-Data File (SDF)](https://en.wikipedia.org/wiki/Chemical_table_file#SDF) format. SDF is a widely used file format for representing molecular structures and associated data, making it suitable for various cheminformatics applications.