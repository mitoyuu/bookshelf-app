## ER図

```mermaid
erDiagram
    users ||--o{ books : "登録"
    users ||--o{ reviews : "投稿"
    books ||--o{ reviews : "レビュー"

    users {
        bigint id PK
        varchar name
        varchar email UK
    }

    books {
        bigint id PK
        bigint user_id FK
        varchar title
        varchar author
    }

    reviews {
        bigint id PK
        bigint book_id FK
        bigint user_id FK
        tinyint rating
        text comment
    }
```