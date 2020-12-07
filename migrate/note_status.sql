CREATE TABLE `note_status`(
    `id` int(11) AUTO_INCREMENT,
    `chat_id` varchar(50) not null,
    `status` int default 0,
    `date` datetime,
    primary key (`id`)
)