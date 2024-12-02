# Curation of COCONUT DB

COCONUT has aggregated NPs data from 63 sources under open source licenses. This data ranged from supplimentary material from published papers to well curated databases under open licenses. It was decided by the Steering Committee that the focus of COCONUT Natural Products database was to provide reliable information on Molecules, Organisms and sample locations within the organisms where these molecules were reported from, and geo-locations where these organisms were reported to be found in literature. In order provide this complete set of information on a compound, wherever it was determined during the curation process that the required information was missing webscraping was utilised. Scraping was also used where source collection data was openly accessible but had no bulk download links.

Following the above processes and principles, the Original 2021 version of COCONUT aggregated 53 openly accessible Collections. 2024 version of COCONUT expanded its database to include 10 more Collections bringing the total to 63. While gathering this data for the new version, the older 53 collection were revisited to ensure inclusion of updates from these sources. Where the Collections are no more accessible, the data that was used in the 2021 version of COCONUT was left as is.

The process of aggregation invovled automated pipelines that minimises manual intervention and ensures the data is of highest quality. Initially data from each collection is passed through RDKit pipeline. Any molecules that failed to get parsed by RDKit were eliminated from further processing. The parsed molecules were then kept as CSV files with all the data provided by the sources. These CSV files are then loaded into COCONUT database preserving the data to trace it back the source. Now, to establish that the chemical structure are valid, ChEMBL pipeline (ChEMBL Structure Curation Pipeline Checker) was used. Any molecules marked with an error code of 6 or higer are marked as rejected and kept for future reference (COCONUT provides tools to make corrections at this stage to rectify any discrepancies that led to these errors and resubmit the molecules). The ones that are marked as passed are then imported into the tables accessible by the users on COCONUT.

  ## Curation flow chart

  <iframe style="border: 1px solid rgba(0, 0, 0, 0.1);" width="600" height="450" src="https://www.figma.com/embed?embed_host=share&url=https%3A%2F%2Fwww.figma.com%2Fboard%2FNXjyhBxyzObP5FuhciKpaE%2FCuration-flow-chart%3Fnode-id%3D0-1%26t%3DYu2YXLQGa7KIvo6O-1" allowfullscreen></iframe>

## Curation tools
One of the features that distinguishes COCONUT 2024 from its predecessor is the Curation Tools. The research community's experience with 2021 version resulted in the realisation that not all the molecules from the Natural Products sources are organic in their origin. Hence rose the demand for ways to curate the molecules on COCONUT 2021. This was realised with the release of COCONUT 2024.

### Reporting
The Curation process begins with Reporting. Any user on COCONUT has the ability to report on discrepancies in data displayed on the platform (Find more about reporting [here](/reporting)). Once a report is created, it stays in the **Draft** state. The user can still edit and make changes to the report at this stage. When the report is ready for submission, user can simply change the status to **Submittted**. This will now go into the Curation queue and gets examined by a Curator on the platform.

::: info COCONUT Curators
Members of the COCONUT steering committee, also take up the role of curator on the platform. They can also confer this role to some selected users who are deemed knowledgable in the field of Natural Products.
:::

If the Curator deems the report to be correct, the report gets approved and necessary action gets taken. For example, if a compound is reported to be synthetic in origin, after curator's enquiry, if is proven to be so, the compound gets deactivated along with a reason why it was deactivated. 

Users may also suggest changes to the details of a compound through reporting and the same curation process is followed.

### Audit trails
To ensure transparency in the curation process and thereby impart authenticity to the data on the platform, COCONUT 2024 also provides for audit trails. Every change to the data displayed on the platform is captured. Who did what and where are tracked internally. Combined with the vetting process for curators, the audit trails prevent any misuse of the platform. Find more about auditing [here](/audit-trail).





<style>
table {
width: 100%;
border-collapse: collapse;
}
th, td {
border: 1px solid #ddd;
padding: 8px;
}
th {
background-color: #f2f2f2;
}
td {
text-align: left;
}
td:nth-child(1), td:nth-child(3) {
text-align: center;
}
</style>
