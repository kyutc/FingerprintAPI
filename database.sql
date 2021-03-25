PRAGMA foreign_keys=ON;

CREATE TABLE users (
    id             INTEGER              PRIMARY KEY AUTOINCREMENT NOT NULL,
    name           CHAR(32)             NOT NULL UNIQUE
);

CREATE TABLE fingerprints (
    id             INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    user_id        int             NOT NULL,
    --finger         char(2)
    --    CHECK(finger IN ('lt','li','lm','lr','lp','rt','ri','rm','rr','rp'))
    --    NOT NULL,
    classification char(1)
        CHECK(classification IN ('l', 'r', 'w', 'a', 't', 's'))
        NOT NULL,
    template       TEXT            NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id)
    --UNIQUE(id, finger),
);
