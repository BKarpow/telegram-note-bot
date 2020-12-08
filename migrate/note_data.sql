CREATE TABLE `note_data`(
    `id` int(11) AUTO_INCREMENT,
    `chat_id` varchar(50) not null,
    `note` varchar(800),
    `date` datetime,
    primary key (`id`)
)