CREATE TABLE IF NOT EXISTS bookings (
    id               UUID          PRIMARY KEY,
    user_id          UUID          NOT NULL,
    room_id          UUID          NOT NULL REFERENCES rooms(id),
    hotel_id         UUID          NOT NULL REFERENCES hotels(id),
    check_in         DATE          NOT NULL,
    check_out        DATE          NOT NULL,
    guests           SMALLINT      NOT NULL CHECK (guests >= 1),
    total_price      NUMERIC(10,2) NOT NULL,
    currency         VARCHAR(3)    NOT NULL DEFAULT 'USD',
    status           VARCHAR(20)   NOT NULL DEFAULT 'pending',
    special_requests TEXT,
    payment_id       UUID,
    created_at       TIMESTAMPTZ   NOT NULL DEFAULT now(),
    CONSTRAINT chk_booking_dates CHECK (check_out > check_in)
);

CREATE INDEX IF NOT EXISTS idx_bookings_room_dates
    ON bookings (room_id, check_in, check_out)
    WHERE status != 'cancelled';