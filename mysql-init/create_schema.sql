USE cloudbeds;

CREATE TABLE intervals
(
    `from` DATETIME NOT NULL,
    `to`   DATETIME NOT NULL,
    price  DECIMAL  NOT NULL,
    PRIMARY KEY (`from`, `to`)
);


