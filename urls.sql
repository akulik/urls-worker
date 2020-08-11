create table urls
(
    id int auto_increment NOT NULL primary key ,
    url varchar(255) NOT NULL ,
    status enum('NEW', 'PROCESSING', 'DONE', 'ERROR') default 'NEW',
    http_code int(3) null
) ENGINE = INNODB DEFAULT CHARSET = utf8;
