drop table if exists user;

create table user (
	id integer not null,
	name varchar(150) not null,
	username varchar(50) not null,
	password varchar(40) not null,
	token varchar(255),
	tokenval datetime,
	last_login datetime
);

insert into user(id,name,username,password,token,tokenval)
	values (1,'Evaldo','evaldo','evaldo123','9I3JJSYEH','2010-04-24 17:15:23');