drop database if exists cmg_test;
create database cmg_test default character set utf8;
grant all on cmg_test.* to 'cmg_test'@'%' identified by '123456';
grant all on cmg_test.* to 'cmg_test'@'localhost' identified by '123456';
flush privileges;
set names utf8;
use cmg_test;
/*----------测试没有join表----------*/
# 表中的col有input/text/file/time四种类型
create table user(
	user_id int not null auto_increment comment '用户id',
	user_name varchar(64) not null comment '用户名',
	user_sex enum('male', 'female', 'unclear') comment '用户性别',
	user_label set('label1', 'label2', 'label3', 'label4', 'label5', 'label6') comment '用户标签',
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
/*----------/测试没有join表----------*/

/*----------测试需要使用JOIN的情况（一对多，多对多）----------*/
# 主表管理员表
CREATE TABLE admin(
	admin_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
	admin_name varchar(64) NOT NULL COMMENT '管理员名',
	role_id varchar(64) NOT NULL DEFAULT 0 COMMENT '角色ID（用“,”分隔）',-- role
	perm_id varchar(64) NOT NULL DEFAULT 0 COMMENT '权限ID（用“,”分隔）',-- permission
	PRIMARY KEY (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '管理员';
insert into admin (admin_name, role_id, perm_id) values 
('admin', '1,2,3', ''),
('root', '1', '4,5'),
('administrator', '2', '1,3');

# 主表游戏表
CREATE TABLE game(
	game_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '游戏ID',
	game_name varchar(64) NOT NULL COMMENT '游戏名',
	PRIMARY KEY (game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '游戏';
insert into game (game_name) values 
('Rewrite'),('Angel Beats!'),('CLANNAD'),('Little Busters!'),('Kanon'),('AIR');

# 连接表管理员_游戏表
CREATE TABLE admin_game(
	admin_id int UNSIGNED NOT NULL COMMENT '管理员ID',-- admin
	game_id int UNSIGNED NOT NULL COMMENT '游戏ID',-- game
	#primary key (admin_id, game_id),
	foreign key(admin_id) references admin(admin_id),
	foreign key(game_id) references game(game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '管理员_游戏';
insert into admin_game (admin_id, game_id) values 
(1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(2,1),(2,2),(2,6),(3,1),(3,2),(3,3);

# 连接表角色表
CREATE TABLE role(
	role_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '角色ID',
	role_name varchar(64) NOT NULL COMMENT '角色',
	perm_id varchar(64) NOT NULL COMMENT '权限ID（用“,”分隔）',-- permission
	PRIMARY KEY (role_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '角色';
insert into role (role_name, perm_id) values 
('Game Maker', '1,2,3,4,5,6,7'),
('Angel Player', '1,2,3,4,5,6,7'),
('Game Player', '5,6,7'),
('Non-Player Character', '1');

# 连接表权限表
CREATE TABLE permission(
	perm_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '权限ID',
	controller varchar(64) NOT NULL COMMENT '模块（控制器）',
	action varchar(64) NOT NULL COMMENT '功能（方法）',
	PRIMARY KEY (perm_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT '权限';
insert into permission (controller, action) values 
('game_source', 'index'),
('game_source', 'insert'),
('game_source', 'update'),
('game_source', 'delete'),
('Steam', 'discount'),
('TGP', 'kekeke'),
('Ubisoft', 'potato');
/*----------/测试需要使用JOIN的情况（一对多，多对多）----------*/

/*----------测试联合主键和联合外键----------*/
# 主表管理员表
CREATE TABLE admin_composite_key(
	admin_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
	admin_name varchar(64) NOT NULL COMMENT '管理员名',
	PRIMARY KEY (admin_id, admin_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '管理员（联合主键）';
insert into admin_composite_key (admin_name) values 
('admin'),
('root'),
('administrator');
# 连接表管理员_游戏表
CREATE TABLE admin_composite_key_game(
	admin_id int UNSIGNED NOT NULL COMMENT '管理员ID',-- admin
	admin_name varchar(64) NOT NULL COMMENT '管理员名',
	game_id int UNSIGNED NOT NULL COMMENT '游戏ID',-- game
	foreign key(admin_id, admin_name) references admin_composite_key(admin_id, admin_name),
	foreign key(game_id) references game(game_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT '管理员_游戏（联合外键）';
insert into admin_composite_key_game (admin_id, admin_name, game_id) values 
(1,'admin',1),(1,'admin',2),(1,'admin',3),(1,'admin',4),(1,'admin',5),(1,'admin',6),
(2,'root',1),(2,'root',2),(2,'root',6),
(3,'administrator',1),(3,'administrator',2),(3,'administrator',3);
/*----------测试联合主键----------*/
