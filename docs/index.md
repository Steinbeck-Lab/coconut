---
layout: home

head:
  - - link
    - rel: stylesheet
      href: https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap

hero:
  name: ""
  text: The COlleCtion of Open NatUral producTs database documentation
  tagline: Your Comprehensive Resource for Open Natural Products
  image:
    src: /logo.png
    alt: COCONUT Logo
  actions:
    - theme: brand
      text: "Get Started"
      link: /introduction
    - theme: alt
      text: "Search Compounds"
      link: https://coconut.naturalproducts.net/search

features:
  - icon: 🌍
    title: "Online Submission and Curation"
    details: "Contribute new data to keep the database current and comprehensive."
  - icon: 🔍
    title: "Search and Filter"
    details: "Advanced search and filtering options to easily find compounds based on specific criteria."
  - icon: 🔗
    title: "API Access"
    details: "Seamless API integration with other tools and databases."

footer:
  message: "COCONUT - Empowering Natural Product Research"
  copyright: "© 2025 COCONUT Database"

---

## Contact Us

<div class="contact-cards">
  <div class="contact-card">
    <h3>Help Desk</h3>
    <p>Any issues or support requests can be raised at our Help Desk or write to us at:</p>
    <a href="mailto:info.COCONUT@uni-jena.de">info.COCONUT@uni-jena.de</a>
  </div>
  <div class="contact-card">
    <h3>Discussion Forum</h3>
    <p>Join our COCONUT Discussion Forum:</p>
    <a href="mailto:coconut-discuss@listserv.uni-jena.de">coconut-discuss@listserv.uni-jena.de</a>
  </div>
</div>

<style>
:root {
  --vp-c-brand: #00a86b;
  --vp-c-brand-light: #00c17c;
  --vp-font-family-base: 'Poppins', sans-serif;
  --bg-light: #f5f5f5;
  --bg-dark: #1e1e1e;
  --text-light: #333;
  --text-dark: #f5f5f5;
}
html {
  background-color: var(--bg-light);
  color: var(--text-light);
}

html.dark {
  background-color: var(--bg-dark);
  color: var(--text-dark);
}
.VPHero .text {
  font-size: 48px;
  font-weight: 700;
  background: linear-gradient(45deg, var(--vp-c-brand), var(--vp-c-brand-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}


.VPHero .tagline {
  font-size: 24px;
  color: var(--text-light);
}

html.dark .VPHero .tagline {
  color: var(--text-dark);
}

.VPFeatures .items {
  gap: 50px;
}

.VPFeatures .item {
  flex: 1;
  background: var(--bg-light);
  border-radius: 12px;
  padding: 20px;
  transition: all 0.3s ease;
}

html.dark .VPFeatures .item {
  background: var(--bg-dark);
}

.VPFeatures .item:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.VPFeatures .icon {
  font-size: 36px;
}

.contact-cards {
  display: flex;
  gap: 24px;
  margin-top: 24px;
}

.contact-card {
  flex: 1;
  background: var(--bg-light);
  border-radius: 12px;
  padding: 24px;
  transition: all 0.3s ease;
}


html.dark .contact-card {
  background: var(--bg-dark);
}

.contact-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.contact-card h3 {
  margin-top: 0;
  color: var(--vp-c-brand);
}

.contact-card a {
  display: inline-block;
  margin-top: 12px;
  color: var(--vp-c-brand);
  text-decoration: none;
  font-weight: 600;
}

.contact-card a:hover {
  text-decoration: underline;
}
</style>
