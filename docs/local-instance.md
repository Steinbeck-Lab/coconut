# Local Instance

COCONUT can be deployed within your organisation's network without exposing it to outer networks for your own needs. This would require a server to host the application and its data. COCONUT, being an open source application, makes its code available at GitHub. You may use the following command to clone it.
Clone the Repository

```bash
git clone https://github.com/Steinbeck-Lab/coconut.git
```



The Dockerfile in the code base has the necessary instructions to build the required image for COCONUT. The docker-compose.yml file has the necessary instructions to pull and run this image and the other dependent images in respective containers.

## Documentation:
Docs related to this application are also provided to facilitate your internal documentation requirements.

## Deployment
 - **On to a VM:** Use the docker-compose.yml file

 ## Helm Chart (Optional)

COCONUT can be packaged and published as [Helm](https://helm.sh/) charts for container orchestration deployment, which makes the installation easy to define, install, and upgrade.
You need to install Helm first to use the charts. Please refer to the Helm's [documentation](https://helm.sh/docs) to get started.

The chart comes with following optional dependencies which you can opt to have in your deployment if you wish to:

- [Meilisearch](https://docs.meilisearch.com/) (Search Engine)
- [RabbitMQ](https://www.rabbitmq.com/documentation.html) (Message Broker)
- [Redis](https://redis.io/documentation) (Cache)

Once Helm has been set up correctly, add the repo as follows:

```bash
helm repo add repo-helm-charts https://nfdi4chem.github.io/repo-helm-charts/
```

If you had already added this repo earlier, run `helm repo update` to retrieve
the latest versions of the packages.  You can then run `helm search repo repo-helm-charts` to see the charts.

Before you install [generate your own application key](https://stackoverflow.com/questions/33370134/when-to-generate-a-new-application-key-in-laravel) and provide that value in the .Values.appProperties.key property.

To install the coconut-app chart:

```
helm install my-coconut-app repo-helm-charts/coconut-app
```

To uninstall the chart:

```
helm delete my-coconut-app
```

To learn more about the structure of the chart, visit our [Github repo](https://github.com/NFDI4Chem/repo-helm-charts).



<!-- ## CI/CD workflows:

For helping the users with automatic deployments, a CI/CD flow is provided in the .github/workflows.
 - **dev-build:** 
    - **Workflow Trigger:** The workflow is triggered by a push event to the `development` branch. This ensures that every time code is pushed to the `development` branch, the workflow is executed.
    - **The workflow is designed to:**
        >- **Build Docker Images**: It builds the latest Docker images for both the application and the Nginx server from the source code.
        >- **Push Docker Images to Container Registry**: The built Docker images are pushed to a container registry for deployment.
 - **docs-deploy:**
    - **Workflow Trigger:** The workflow is triggered by a push event to the `development` branch. This ensures that every time code is pushed to the `development` branch, the workflow is executed.
        - **Pushes to the `development` branch**: Whenever code is pushed to the `development` branch, the workflow runs.
        - **Manual dispatch**: The workflow can also be manually triggered from the Actions tab in the GitHub repository.
    - **The workflow is designed to:**
        >- **Build the VitePress documentation site**: It installs dependencies and builds the site using VitePress.
        >- **Deploy the built site to GitHub Pages**: It uploads the built site to GitHub Pages, making the documentation publicly available.

 - **release-please:**
    - **Workflow Trigger:** The workflow is activated by any push to the `main` branch. This ensures that every change merged into the main branch can trigger a potential new release.
    - **The workflow is designed to:**
        >- **Trigger the release-please action**: Automatically creates a new release when code changes are pushed to the `main` branch.
        >- **Handles PHP-specific release**: The workflow is configured to manage releases for a PHP package.

This document provides an overview of the GitHub Actions workflow designed to automatically create releases for the Coconut project using the `release-please` action. The workflow is triggered every time code is pushed to the `main` branch. -->