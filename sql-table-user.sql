create table user (
	id integer not null auto_increment primary key,
	name varchar(150) not null,
	username varchar(50) not null,
	password varchar(40) not null,
	token varchar(255),
	tokenval datetime
);