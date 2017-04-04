drop database if exists cmg_test;
create database cmg_test default character set utf8;
grant all on cmg_test.* to 'cmg_test'@'%' identified by '123456';
grant all on cmg_test.* to 'cmg_test'@'localhost' identified by '123456';
flush privileges;
set names utf8;
use cmg_test;

# 测试没有join表，表中的col有input/text/file/time四种类型
drop table if exists `user`;
create table user(
	user_id int not null auto_increment comment '用户id',
	user_name varchar(10) not null comment '用户名',
	info text not null comment '用户信息',
	profile varchar(200) comment '用户头像',
	birthday date not null comment '用户生日',
	get_up_time time not null comment '起床时间',
	gift_time timestamp not null comment '收礼物的时间',
	primary key (user_id)
) engine=InnoDB default charset=utf8 comment '用户';
insert into user (user_name, info, profile, birthday, get_up_time, gift_time) values 
('admin', '好人', null, '1000-01-01 00:00:00', '10:59:59', '2000-2-22 22:22:22'),
('root', '好人', null, '1000-01-01 00:00:00', '10:59:59', '2000-2-22 22:22:22'),
('administrator', '好人', null, '1000-01-01 00:00:00', '10:59:59', '2000-2-22 22:22:22');

