CREATE TABLE IF NOT EXISTS hotels (
    id          UUID             PRIMARY KEY,
    name        VARCHAR(255)     NOT NULL,
    description TEXT             NOT NULL,
    street      VARCHAR(255)     NOT NULL,
    city        VARCHAR(100)     NOT NULL,
    country     VARCHAR(100)     NOT NULL,
    postal_code VARCHAR(20)      NOT NULL,
    latitude    DOUBLE PRECISION NOT NULL,
    longitude   DOUBLE PRECISION NOT NULL,
    stars       SMALLINT         NOT NULL CHECK (stars BETWEEN 1 AND 5),
    manager_id  UUID             NOT NULL,
    status      VARCHAR(20)      NOT NULL DEFAULT 'active',
    created_at  TIMESTAMPTZ      NOT NULL DEFAULT now()
);