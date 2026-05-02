CREATE TABLE IF NOT EXISTS rooms (
    id               UUID         PRIMARY KEY,
    hotel_id         UUID         NOT NULL REFERENCES hotels(id) ON DELETE CASCADE,
    type             VARCHAR(20)  NOT NULL,
    room_number      VARCHAR(20)  NOT NULL,
    capacity         SMALLINT     NOT NULL CHECK (capacity BETWEEN 1 AND 50),
    price_per_night  NUMERIC(10,2) NOT NULL,
    currency         VARCHAR(3)   NOT NULL DEFAULT 'USD',
    status           VARCHAR(20)  NOT NULL DEFAULT 'available',
    created_at       TIMESTAMPTZ  NOT NULL DEFAULT now()
);