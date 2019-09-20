USE cloudbeds;

CREATE TABLE intervals
(
    `from` DATETIME NOT NULL,
    `to`   DATETIME NOT NULL,
    price  DECIMAL(6,2)  NOT NULL,
    PRIMARY KEY (`from`, `to`)
);
