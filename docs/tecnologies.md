# Tecnologies i arquitectura

## Stack
- Web pública: GitHub Pages (HTML/CSS/JS)
- Panel intern: Flask (Python) + MySQL/MariaDB
- LAB simulat: generació CSV (C++ o Python) i ingesta cap a DB
- (Opcional) Docker Compose: db, api, adminer, (mqtt)

## Diagrama d'integració
```mermaid
flowchart LR
  subgraph Públic
    P[GitHub Pages /docs]
  end
  subgraph Intern
    A[Flask Panel]
    D[(MySQL/MariaDB)]
    L[LAB Ingestor]
  end
  Client -->|Reserves/Calculadores| P
  Entrenador --> A
  A --> D
  L --> D
  P -->|enllaços| A
