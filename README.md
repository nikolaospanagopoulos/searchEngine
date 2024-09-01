# PHP Search Engine

A fully-featured PHP-based search engine with telemetry and image search capabilities. This project is fully Dockerized, making it easy to deploy and manage.

## Features

- **Search Engine:** Implements a robust search functionality for text and images.
- **Crawling:** Using recursion, crawls through links gathering information such us urls and images. It follows google standards and follows robots.txt instructions.
- **Telemetry:** Collects and analyzes user data for improving search results and user experience.
- **Dockerized:** The application is fully containerized using Docker, simplifying deployment and scaling.

## Requirements

To run this project, you need to have the following installed:

- **Docker** - Container platform to run the application in isolated environments.
- **Docker Compose** - Tool for defining and running multi-container Docker applications.

## Setup Instructions

Clone the repository to your local machine:

```bash
git clone git@github.com:nikolaospanagopoulos/searchEngine.git
```

Enter the folder

```bash
cd searchEngine
```

Use Docker Compose to build and run the application

```bash
docker-compose up --build
```

Go to

```bash
http://localhost:8000
```

Crawl websites by going to (wait for about 10 minutes or finish it by closing the browser tab):

```bash
http://localhost:8000/crawl.php
```

Some ideas to use for testing the search:
news, economy, elections, cnn, bbc etc
