CREATE DATABASE IF NOT EXISTS trabalho_web;

USE trabalho_web;

CREATE TABLE
    IF NOT EXISTS recados (
        id INT PRIMARY KEY AUTO_INCREMENT,
        mensagem TEXT NOT NULL,
        status TINYINT (1) NOT NULL DEFAULT 0,
        data_criacao TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );