import{_ as t,c as a,o as e,a1 as s}from"./chunks/framework.D0pIZSx4.js";const f=JSON.parse('{"title":"Download","description":"","frontmatter":{},"headers":[],"relativePath":"download.md","filePath":"download.md"}'),o={name:"download.md"},i=s('<h1 id="download" tabindex="-1">Download <a class="header-anchor" href="#download" aria-label="Permalink to &quot;Download&quot;">​</a></h1><p>Coconut online provides users with various download options listed below, offering a convenient means to obtain chemical structures of natural products in a widely accepted and machine-readable format.</p><ul><li>Download the COCONUT dataset as a Postgres dump.</li><li>Download Natural Products Structures in Canonical and Absolute <a href="https://en.wikipedia.org/wiki/Simplified_molecular-input_line-entry_system" target="_blank" rel="noreferrer">SMILES</a> format.</li><li>Download Natural Products Structures in <a href="https://en.wikipedia.org/wiki/Chemical_table_file#SDF" target="_blank" rel="noreferrer">SDF</a> format.</li></ul><p>At the end of each month, precisely at 00:00 CET, a snapshot of the Coconut data is taken and archived in an S3 storage bucket. To obtain the dump file of the most recent snapshot through UI, navigate to the left panel of your dashboard and locate the <a href="https://coconut.naturalproducts.net/download" target="_blank" rel="noreferrer">Download</a> button. Click on the <a href="https://coconut.naturalproducts.net/download" target="_blank" rel="noreferrer">Download</a> with option as desired and this will initiate the download of the data file containing the latest snapshot.</p><p>To access data through the API, refer to the <a href="/coconut/download-api.html">API</a> or <a href="https://dev.coconut.naturalproducts.net/api/documentation" target="_blank" rel="noreferrer">Swagger</a> documentation for instructions on downloading the data.</p><div class="warning custom-block"><p class="custom-block-title">WARNING</p><p>Please note that the COCONUT dataset is subject to certain terms of use and licensing restrictions. Make sure to review and comply with the respective terms and conditions associated with the dataset.</p></div><h2 id="download-the-coconut-dataset-as-a-postgres-dump" tabindex="-1">Download the COCONUT dataset as a Postgres dump <a class="header-anchor" href="#download-the-coconut-dataset-as-a-postgres-dump" aria-label="Permalink to &quot;Download the COCONUT dataset as a Postgres dump&quot;">​</a></h2><p>This functionality allows you to obtain the comprehensive COCONUT dataset in the form of a Postgres dump file. Once you have downloaded the <a href="https://www.postgresql.org/docs/current/app-pgdump.html#:~:text=pg_dump%20is%20a%20utility%20for,only%20dumps%20a%20single%20database." target="_blank" rel="noreferrer">Postgres dump</a> file, you can import it into your local Postgres database management system by following the below instruction, which will allow you to explore, query, and analyze the COCONUT dataset using SQL statements within your own environment.</p><div class="info custom-block"><p class="custom-block-title">INFO</p><p>The Postgres dump exclusively comprises data only from the following tables: molecules, properties, and citations.</p></div><h3 id="instruction-to-restore" tabindex="-1">Instruction to restore <a class="header-anchor" href="#instruction-to-restore" aria-label="Permalink to &quot;Instruction to restore&quot;">​</a></h3><p>To restore the database using the dump file, follow these instructions:</p><ul><li><p>Make sure that Postgres (version 14.0 or higher) is up and running on your system.</p></li><li><p>Unzip the downloaded dump file.</p></li><li><p>To import, run the below command by replacing the database name and username with yours and enter the password when prompted.</p></li></ul><div class="language-bash vp-adaptive-theme"><button title="Copy Code" class="copy"></button><span class="lang">bash</span><pre class="shiki shiki-themes github-light github-dark vp-code" tabindex="0"><code><span class="line"><span style="--shiki-light:#6F42C1;--shiki-dark:#B392F0;">psql</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> -h</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> 127.0.0.1</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> -p</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> 5432</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> -d</span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;"> &lt;</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;"> database</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;"> name</span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;"> &gt;</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> -U</span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;"> &lt;</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;"> username</span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;"> &gt;</span><span style="--shiki-light:#005CC5;--shiki-dark:#79B8FF;"> -W</span><span style="--shiki-light:#D73A49;--shiki-dark:#F97583;"> &lt;</span><span style="--shiki-light:#032F62;--shiki-dark:#9ECBFF;"> postgresql-coconut.sql</span></span></code></pre></div><h2 id="download-natural-products-structures-in-canonical-and-absolute-smiles-format" tabindex="-1">Download Natural Products Structures in Canonical and Absolute SMILES format <a class="header-anchor" href="#download-natural-products-structures-in-canonical-and-absolute-smiles-format" aria-label="Permalink to &quot;Download Natural Products Structures in Canonical and Absolute SMILES format&quot;">​</a></h2><p>The &quot;Download Natural Products Structures in SMILES format&quot; API provides a convenient way to obtain the chemical structures of natural products in the Cannonical <a href="https://en.wikipedia.org/wiki/Simplified_molecular-input_line-entry_system" target="_blank" rel="noreferrer">Simplified Molecular Input Line Entry System (SMILES)</a> and Absolute SMILES format. This format represents molecular structures using a string of ASCII characters, allowing for easy storage, sharing, and processing of chemical information.</p><h2 id="download-natural-products-structures-in-sdf-format" tabindex="-1">Download Natural Products Structures in SDF format <a class="header-anchor" href="#download-natural-products-structures-in-sdf-format" aria-label="Permalink to &quot;Download Natural Products Structures in SDF format&quot;">​</a></h2><p>This functionality provides a convenient way to access the chemical structures of natural products in the <a href="https://en.wikipedia.org/wiki/Chemical_table_file#SDF" target="_blank" rel="noreferrer">Structure-Data File (SDF)</a> format. SDF is a widely used file format for representing molecular structures and associated data, making it suitable for various cheminformatics applications.</p>',17),n=[i];function r(l,d,h,u,c,p){return e(),a("div",null,n)}const k=t(o,[["render",r]]);export{f as __pageData,k as default};
