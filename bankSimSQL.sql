create table tb_users(
id int primary key auto_increment not null,
name varchar(50) not null,
email varchar(60) unique not null,
password varchar(255) not null,
created_at timestamp default now());

create table tb_accounts(
id int primary key auto_increment not null,
balance decimal(13,2) default 0,
user_id int not null,
created_at timestamp default now(),
foreign key(user_id) references tb_users(id)
);

create table tb_loans(
id int primary key auto_increment not null,
loan_amount decimal(13,2),
created_at timestamp default now(),
interest decimal(5,3),
months int,
amount_left decimal(13,2),
account_id int not null,
foreign key(account_id) references tb_accounts(id));

CREATE TABLE IF NOT EXISTS `tb_transaction_history` (
  `id` INT UNIQUE NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `account_id` INT NOT NULL,
  `transaction_value` DECIMAL(13,2) NOT NULL DEFAULT 0.00,
  `loan_id` INT NULL,     FOREIGN KEY (`account_id`)
    REFERENCES `bank`.`tb_accounts` (`id`))