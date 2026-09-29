-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Хост: MySQL-8.0
-- Время создания: Фев 03 2026 г., 16:06
-- Версия сервера: 8.0.41
-- Версия PHP: 8.2.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `cinema_db`
--

DELIMITER $$
--
-- Процедуры
--
CREATE DEFINER=`root`@`%` PROCEDURE `GenerateSeatsForSession` (IN `sessionId` INT)   BEGIN
    DECLARE sectionNum INT;
    DECLARE rowNum INT;
    DECLARE seatPos INT;
    DECLARE seatLetter CHAR(1);
    
    SET sectionNum = 1;
    
    WHILE sectionNum <= 4 DO
        SET rowNum = 1;
        WHILE rowNum <= 5 DO
            SET seatPos = 1;
            WHILE seatPos <= 4 DO
                SET seatLetter = CHAR(ASCII('A') + rowNum - 1);
                INSERT INTO seats (session_id, seat_number, section, seat_row, seat_col)
                VALUES (
                    sessionId,
                    CONCAT(sectionNum, seatLetter, seatPos),
                    sectionNum,
                    rowNum,
                    seatPos
                );
                SET seatPos = seatPos + 1;
            END WHILE;
            SET rowNum = rowNum + 1;
        END WHILE;
        SET sectionNum = sectionNum + 1;
    END WHILE;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `movies`
--

CREATE TABLE `movies` (
  `id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL,
  `poster_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `release_year` int DEFAULT NULL,
  `director` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `movies`
--

INSERT INTO `movies` (`id`, `title`, `description`, `duration_minutes`, `poster_url`, `release_year`, `director`, `created_at`) VALUES
(1, 'Интерстеллар', 'Когда засуха приводит человечество к продовольственному кризису, коллектив исследователей и учёных отправляется сквозь червоточину в путешествие, чтобы превзойти прежние ограничения для космических путешествий человека и переселить человечество на другую планету.', 169, NULL, 2014, 'Кристофер Нолан', '2026-02-03 08:23:40'),
(2, 'Начало', 'Кобб — талантливый вор, лучший из лучших в опасном искусстве извлечения: он крадет ценные секреты из глубин подсознания во время сна, когда человеческий разум наиболее уязвим.', 148, NULL, 2010, 'Кристофер Нолан', '2026-02-03 08:23:40'),
(3, 'Матрица', 'Хакер Нео узнает, что его мир — виртуальная реальность, созданная всемогущими машинами, и присоединяется к повстанцам, чтобы сражаться за освобождение человечества.', 136, NULL, 1999, 'Лана и Лилли Вачовски', '2026-02-03 08:23:40'),
(4, 'Побег из Шоушенка', 'Бухгалтер Энди Дюфрейн обвинён в убийстве собственной жены и её любовника. Оказавшись в тюрьме под названием Шоушенк, он сталкивается с жестокостью и беззаконием, царящими по обе стороны решётки.', 142, NULL, 1994, 'Фрэнк Дарабонт', '2026-02-03 08:23:40');

-- --------------------------------------------------------

--
-- Структура таблицы `seats`
--

CREATE TABLE `seats` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `seat_number` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `section` int NOT NULL,
  `seat_row` int NOT NULL,
  `seat_col` int NOT NULL,
  `seat_status` enum('available','booked','sold') COLLATE utf8mb4_unicode_ci DEFAULT 'available',
  `user_id` int DEFAULT NULL,
  `booked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `seats`
--

INSERT INTO `seats` (`id`, `session_id`, `seat_number`, `section`, `seat_row`, `seat_col`, `seat_status`, `user_id`, `booked_until`) VALUES
(1, 1, '1', 1, 1, 1, 'available', NULL, NULL),
(2, 1, '2', 1, 1, 2, 'available', NULL, NULL),
(3, 1, '3', 1, 1, 3, 'available', NULL, NULL),
(4, 1, '4', 1, 1, 4, 'available', NULL, NULL),
(5, 1, '5', 1, 2, 1, 'available', NULL, NULL),
(6, 1, '6', 1, 2, 2, 'available', NULL, NULL),
(7, 1, '7', 1, 2, 3, 'available', NULL, NULL),
(8, 1, '8', 1, 2, 4, 'available', NULL, NULL),
(9, 1, '9', 1, 3, 1, 'available', NULL, NULL),
(10, 1, '10', 1, 3, 2, 'available', NULL, NULL),
(11, 1, '11', 1, 3, 3, 'available', NULL, NULL),
(12, 1, '12', 1, 3, 4, 'available', NULL, NULL),
(13, 1, '13', 1, 4, 1, 'available', NULL, NULL),
(14, 1, '14', 1, 4, 2, 'available', NULL, NULL),
(15, 1, '15', 1, 4, 3, 'available', NULL, NULL),
(16, 1, '16', 1, 4, 4, 'available', NULL, NULL),
(17, 1, '17', 1, 5, 1, 'available', NULL, NULL),
(18, 1, '18', 1, 5, 2, 'available', NULL, NULL),
(19, 1, '19', 1, 5, 3, 'available', NULL, NULL),
(20, 1, '20', 1, 5, 4, 'available', NULL, NULL),
(21, 1, '21', 2, 1, 1, 'available', NULL, NULL),
(22, 1, '22', 2, 1, 2, 'available', NULL, NULL),
(23, 1, '23', 2, 1, 3, 'available', NULL, NULL),
(24, 1, '24', 2, 1, 4, 'available', NULL, NULL),
(25, 1, '25', 2, 2, 1, 'available', NULL, NULL),
(26, 1, '2B2', 2, 2, 2, 'available', NULL, NULL),
(27, 1, '2B3', 2, 2, 3, 'available', NULL, NULL),
(28, 1, '2B4', 2, 2, 4, 'available', NULL, NULL),
(29, 1, '2C1', 2, 3, 1, 'available', NULL, NULL),
(30, 1, '2C2', 2, 3, 2, 'available', NULL, NULL),
(31, 1, '2C3', 2, 3, 3, 'available', NULL, NULL),
(32, 1, '2C4', 2, 3, 4, 'available', NULL, NULL),
(33, 1, '2D1', 2, 4, 1, 'available', NULL, NULL),
(34, 1, '2D2', 2, 4, 2, 'available', NULL, NULL),
(35, 1, '2D3', 2, 4, 3, 'available', NULL, NULL),
(36, 1, '2D4', 2, 4, 4, 'available', NULL, NULL),
(37, 1, '2E1', 2, 5, 1, 'available', NULL, NULL),
(38, 1, '2E2', 2, 5, 2, 'available', NULL, NULL),
(39, 1, '2E3', 2, 5, 3, 'available', NULL, NULL),
(40, 1, '2E4', 2, 5, 4, 'available', NULL, NULL),
(41, 1, '3A1', 3, 1, 1, 'available', NULL, NULL),
(42, 1, '3A2', 3, 1, 2, 'available', NULL, NULL),
(43, 1, '3A3', 3, 1, 3, 'available', NULL, NULL),
(44, 1, '3A4', 3, 1, 4, 'available', NULL, NULL),
(45, 1, '3B1', 3, 2, 1, 'available', NULL, NULL),
(46, 1, '3B2', 3, 2, 2, 'available', NULL, NULL),
(47, 1, '3B3', 3, 2, 3, 'available', NULL, NULL),
(48, 1, '3B4', 3, 2, 4, 'available', NULL, NULL),
(49, 1, '3C1', 3, 3, 1, 'available', NULL, NULL),
(50, 1, '3C2', 3, 3, 2, 'available', NULL, NULL),
(51, 1, '3C3', 3, 3, 3, 'available', NULL, NULL),
(52, 1, '3C4', 3, 3, 4, 'available', NULL, NULL),
(53, 1, '3D1', 3, 4, 1, 'available', NULL, NULL),
(54, 1, '3D2', 3, 4, 2, 'available', NULL, NULL),
(55, 1, '3D3', 3, 4, 3, 'available', NULL, NULL),
(56, 1, '3D4', 3, 4, 4, 'available', NULL, NULL),
(57, 1, '3E1', 3, 5, 1, 'available', NULL, NULL),
(58, 1, '3E2', 3, 5, 2, 'available', NULL, NULL),
(59, 1, '3E3', 3, 5, 3, 'available', NULL, NULL),
(60, 1, '3E4', 3, 5, 4, 'available', NULL, NULL),
(61, 1, '4A1', 4, 1, 1, 'available', NULL, NULL),
(62, 1, '4A2', 4, 1, 2, 'available', NULL, NULL),
(63, 1, '4A3', 4, 1, 3, 'available', NULL, NULL),
(64, 1, '4A4', 4, 1, 4, 'available', NULL, NULL),
(65, 1, '4B1', 4, 2, 1, 'available', NULL, NULL),
(66, 1, '4B2', 4, 2, 2, 'available', NULL, NULL),
(67, 1, '4B3', 4, 2, 3, 'available', NULL, NULL),
(68, 1, '4B4', 4, 2, 4, 'available', NULL, NULL),
(69, 1, '4C1', 4, 3, 1, 'available', NULL, NULL),
(70, 1, '4C2', 4, 3, 2, 'available', NULL, NULL),
(71, 1, '4C3', 4, 3, 3, 'available', NULL, NULL),
(72, 1, '4C4', 4, 3, 4, 'available', NULL, NULL),
(73, 1, '4D1', 4, 4, 1, 'available', NULL, NULL),
(74, 1, '4D2', 4, 4, 2, 'available', NULL, NULL),
(75, 1, '4D3', 4, 4, 3, 'available', NULL, NULL),
(76, 1, '4D4', 4, 4, 4, 'available', NULL, NULL),
(77, 1, '4E1', 4, 5, 1, 'available', NULL, NULL),
(78, 1, '4E2', 4, 5, 2, 'available', NULL, NULL),
(79, 1, '4E3', 4, 5, 3, 'available', NULL, NULL),
(80, 1, '4E4', 4, 5, 4, 'available', NULL, NULL),
(81, 2, '1A1', 1, 1, 1, 'available', NULL, NULL),
(82, 2, '1A2', 1, 1, 2, 'available', NULL, NULL),
(83, 2, '1A3', 1, 1, 3, 'available', NULL, NULL),
(84, 2, '1A4', 1, 1, 4, 'available', NULL, NULL),
(85, 2, '1B1', 1, 2, 1, 'available', NULL, NULL),
(86, 2, '1B2', 1, 2, 2, 'available', NULL, NULL),
(87, 2, '1B3', 1, 2, 3, 'available', NULL, NULL),
(88, 2, '1B4', 1, 2, 4, 'available', NULL, NULL),
(89, 2, '1C1', 1, 3, 1, 'available', NULL, NULL),
(90, 2, '1C2', 1, 3, 2, 'available', NULL, NULL),
(91, 2, '1C3', 1, 3, 3, 'available', NULL, NULL),
(92, 2, '1C4', 1, 3, 4, 'available', NULL, NULL),
(93, 2, '1D1', 1, 4, 1, 'available', NULL, NULL),
(94, 2, '1D2', 1, 4, 2, 'available', NULL, NULL),
(95, 2, '1D3', 1, 4, 3, 'available', NULL, NULL),
(96, 2, '1D4', 1, 4, 4, 'available', NULL, NULL),
(97, 2, '1E1', 1, 5, 1, 'available', NULL, NULL),
(98, 2, '1E2', 1, 5, 2, 'available', NULL, NULL),
(99, 2, '1E3', 1, 5, 3, 'available', NULL, NULL),
(100, 2, '1E4', 1, 5, 4, 'available', NULL, NULL),
(101, 2, '2A1', 2, 1, 1, 'available', NULL, NULL),
(102, 2, '2A2', 2, 1, 2, 'available', NULL, NULL),
(103, 2, '2A3', 2, 1, 3, 'available', NULL, NULL),
(104, 2, '2A4', 2, 1, 4, 'available', NULL, NULL),
(105, 2, '2B1', 2, 2, 1, 'available', NULL, NULL),
(106, 2, '2B2', 2, 2, 2, 'available', NULL, NULL),
(107, 2, '2B3', 2, 2, 3, 'available', NULL, NULL),
(108, 2, '2B4', 2, 2, 4, 'available', NULL, NULL),
(109, 2, '2C1', 2, 3, 1, 'available', NULL, NULL),
(110, 2, '2C2', 2, 3, 2, 'available', NULL, NULL),
(111, 2, '2C3', 2, 3, 3, 'available', NULL, NULL),
(112, 2, '2C4', 2, 3, 4, 'available', NULL, NULL),
(113, 2, '2D1', 2, 4, 1, 'available', NULL, NULL),
(114, 2, '2D2', 2, 4, 2, 'available', NULL, NULL),
(115, 2, '2D3', 2, 4, 3, 'available', NULL, NULL),
(116, 2, '2D4', 2, 4, 4, 'available', NULL, NULL),
(117, 2, '2E1', 2, 5, 1, 'available', NULL, NULL),
(118, 2, '2E2', 2, 5, 2, 'available', NULL, NULL),
(119, 2, '2E3', 2, 5, 3, 'available', NULL, NULL),
(120, 2, '2E4', 2, 5, 4, 'available', NULL, NULL),
(121, 2, '3A1', 3, 1, 1, 'available', NULL, NULL),
(122, 2, '3A2', 3, 1, 2, 'available', NULL, NULL),
(123, 2, '3A3', 3, 1, 3, 'available', NULL, NULL),
(124, 2, '3A4', 3, 1, 4, 'available', NULL, NULL),
(125, 2, '3B1', 3, 2, 1, 'available', NULL, NULL),
(126, 2, '3B2', 3, 2, 2, 'available', NULL, NULL),
(127, 2, '3B3', 3, 2, 3, 'available', NULL, NULL),
(128, 2, '3B4', 3, 2, 4, 'available', NULL, NULL),
(129, 2, '3C1', 3, 3, 1, 'available', NULL, NULL),
(130, 2, '3C2', 3, 3, 2, 'available', NULL, NULL),
(131, 2, '3C3', 3, 3, 3, 'available', NULL, NULL),
(132, 2, '3C4', 3, 3, 4, 'available', NULL, NULL),
(133, 2, '3D1', 3, 4, 1, 'available', NULL, NULL),
(134, 2, '3D2', 3, 4, 2, 'available', NULL, NULL),
(135, 2, '3D3', 3, 4, 3, 'available', NULL, NULL),
(136, 2, '3D4', 3, 4, 4, 'available', NULL, NULL),
(137, 2, '3E1', 3, 5, 1, 'available', NULL, NULL),
(138, 2, '3E2', 3, 5, 2, 'available', NULL, NULL),
(139, 2, '3E3', 3, 5, 3, 'available', NULL, NULL),
(140, 2, '3E4', 3, 5, 4, 'available', NULL, NULL),
(141, 2, '4A1', 4, 1, 1, 'available', NULL, NULL),
(142, 2, '4A2', 4, 1, 2, 'available', NULL, NULL),
(143, 2, '4A3', 4, 1, 3, 'available', NULL, NULL),
(144, 2, '4A4', 4, 1, 4, 'available', NULL, NULL),
(145, 2, '4B1', 4, 2, 1, 'available', NULL, NULL),
(146, 2, '4B2', 4, 2, 2, 'available', NULL, NULL),
(147, 2, '4B3', 4, 2, 3, 'available', NULL, NULL),
(148, 2, '4B4', 4, 2, 4, 'available', NULL, NULL),
(149, 2, '4C1', 4, 3, 1, 'available', NULL, NULL),
(150, 2, '4C2', 4, 3, 2, 'available', NULL, NULL),
(151, 2, '4C3', 4, 3, 3, 'available', NULL, NULL),
(152, 2, '4C4', 4, 3, 4, 'available', NULL, NULL),
(153, 2, '4D1', 4, 4, 1, 'available', NULL, NULL),
(154, 2, '4D2', 4, 4, 2, 'available', NULL, NULL),
(155, 2, '4D3', 4, 4, 3, 'available', NULL, NULL),
(156, 2, '4D4', 4, 4, 4, 'available', NULL, NULL),
(157, 2, '4E1', 4, 5, 1, 'available', NULL, NULL),
(158, 2, '4E2', 4, 5, 2, 'available', NULL, NULL),
(159, 2, '4E3', 4, 5, 3, 'available', NULL, NULL),
(160, 2, '4E4', 4, 5, 4, 'available', NULL, NULL),
(161, 3, '1A1', 1, 1, 1, 'sold', 3, '2026-02-03 15:09:40'),
(162, 3, '1A2', 1, 1, 2, 'sold', 3, '2026-02-03 15:09:40'),
(163, 3, '1A3', 1, 1, 3, 'sold', 3, '2026-02-03 15:09:40'),
(164, 3, '1A4', 1, 1, 4, 'sold', 3, '2026-02-03 15:09:40'),
(165, 3, '1B1', 1, 2, 1, 'sold', 3, '2026-02-03 15:09:40'),
(166, 3, '1B2', 1, 2, 2, 'available', NULL, NULL),
(167, 3, '1B3', 1, 2, 3, 'available', NULL, NULL),
(168, 3, '1B4', 1, 2, 4, 'available', NULL, NULL),
(169, 3, '1C1', 1, 3, 1, 'available', NULL, NULL),
(170, 3, '1C2', 1, 3, 2, 'available', NULL, NULL),
(171, 3, '1C3', 1, 3, 3, 'available', NULL, NULL),
(172, 3, '1C4', 1, 3, 4, 'available', NULL, NULL),
(173, 3, '1D1', 1, 4, 1, 'available', NULL, NULL),
(174, 3, '1D2', 1, 4, 2, 'available', NULL, NULL),
(175, 3, '1D3', 1, 4, 3, 'available', NULL, NULL),
(176, 3, '1D4', 1, 4, 4, 'available', NULL, NULL),
(177, 3, '1E1', 1, 5, 1, 'available', NULL, NULL),
(178, 3, '1E2', 1, 5, 2, 'available', NULL, NULL),
(179, 3, '1E3', 1, 5, 3, 'available', NULL, NULL),
(180, 3, '1E4', 1, 5, 4, 'available', NULL, NULL),
(181, 3, '2A1', 2, 1, 1, 'available', NULL, NULL),
(182, 3, '2A2', 2, 1, 2, 'available', NULL, NULL),
(183, 3, '2A3', 2, 1, 3, 'available', NULL, NULL),
(184, 3, '2A4', 2, 1, 4, 'available', NULL, NULL),
(185, 3, '2B1', 2, 2, 1, 'available', NULL, NULL),
(186, 3, '2B2', 2, 2, 2, 'available', NULL, NULL),
(187, 3, '2B3', 2, 2, 3, 'available', NULL, NULL),
(188, 3, '2B4', 2, 2, 4, 'available', NULL, NULL),
(189, 3, '2C1', 2, 3, 1, 'available', NULL, NULL),
(190, 3, '2C2', 2, 3, 2, 'available', NULL, NULL),
(191, 3, '2C3', 2, 3, 3, 'available', NULL, NULL),
(192, 3, '2C4', 2, 3, 4, 'available', NULL, NULL),
(193, 3, '2D1', 2, 4, 1, 'available', NULL, NULL),
(194, 3, '2D2', 2, 4, 2, 'available', NULL, NULL),
(195, 3, '2D3', 2, 4, 3, 'available', NULL, NULL),
(196, 3, '2D4', 2, 4, 4, 'available', NULL, NULL),
(197, 3, '2E1', 2, 5, 1, 'available', NULL, NULL),
(198, 3, '2E2', 2, 5, 2, 'available', NULL, NULL),
(199, 3, '2E3', 2, 5, 3, 'available', NULL, NULL),
(200, 3, '2E4', 2, 5, 4, 'available', NULL, NULL),
(201, 3, '3A1', 3, 1, 1, 'available', NULL, NULL),
(202, 3, '3A2', 3, 1, 2, 'available', NULL, NULL),
(203, 3, '3A3', 3, 1, 3, 'available', NULL, NULL),
(204, 3, '3A4', 3, 1, 4, 'available', NULL, NULL),
(205, 3, '3B1', 3, 2, 1, 'available', NULL, NULL),
(206, 3, '3B2', 3, 2, 2, 'available', NULL, NULL),
(207, 3, '3B3', 3, 2, 3, 'available', NULL, NULL),
(208, 3, '3B4', 3, 2, 4, 'available', NULL, NULL),
(209, 3, '3C1', 3, 3, 1, 'available', NULL, NULL),
(210, 3, '3C2', 3, 3, 2, 'available', NULL, NULL),
(211, 3, '3C3', 3, 3, 3, 'available', NULL, NULL),
(212, 3, '3C4', 3, 3, 4, 'available', NULL, NULL),
(213, 3, '3D1', 3, 4, 1, 'available', NULL, NULL),
(214, 3, '3D2', 3, 4, 2, 'available', NULL, NULL),
(215, 3, '3D3', 3, 4, 3, 'available', NULL, NULL),
(216, 3, '3D4', 3, 4, 4, 'available', NULL, NULL),
(217, 3, '3E1', 3, 5, 1, 'available', NULL, NULL),
(218, 3, '3E2', 3, 5, 2, 'available', NULL, NULL),
(219, 3, '3E3', 3, 5, 3, 'available', NULL, NULL),
(220, 3, '3E4', 3, 5, 4, 'available', NULL, NULL),
(221, 3, '4A1', 4, 1, 1, 'available', NULL, NULL),
(222, 3, '4A2', 4, 1, 2, 'available', NULL, NULL),
(223, 3, '4A3', 4, 1, 3, 'available', NULL, NULL),
(224, 3, '4A4', 4, 1, 4, 'available', NULL, NULL),
(225, 3, '4B1', 4, 2, 1, 'available', NULL, NULL),
(226, 3, '4B2', 4, 2, 2, 'available', NULL, NULL),
(227, 3, '4B3', 4, 2, 3, 'available', NULL, NULL),
(228, 3, '4B4', 4, 2, 4, 'available', NULL, NULL),
(229, 3, '4C1', 4, 3, 1, 'available', NULL, NULL),
(230, 3, '4C2', 4, 3, 2, 'available', NULL, NULL),
(231, 3, '4C3', 4, 3, 3, 'available', NULL, NULL),
(232, 3, '4C4', 4, 3, 4, 'available', NULL, NULL),
(233, 3, '4D1', 4, 4, 1, 'available', NULL, NULL),
(234, 3, '4D2', 4, 4, 2, 'available', NULL, NULL),
(235, 3, '4D3', 4, 4, 3, 'available', NULL, NULL),
(236, 3, '4D4', 4, 4, 4, 'available', NULL, NULL),
(237, 3, '4E1', 4, 5, 1, 'available', NULL, NULL),
(238, 3, '4E2', 4, 5, 2, 'available', NULL, NULL),
(239, 3, '4E3', 4, 5, 3, 'available', NULL, NULL),
(240, 3, '4E4', 4, 5, 4, 'available', NULL, NULL),
(241, 4, '1A1', 1, 1, 1, 'available', NULL, NULL),
(242, 4, '1A2', 1, 1, 2, 'available', NULL, NULL),
(243, 4, '1A3', 1, 1, 3, 'available', NULL, NULL),
(244, 4, '1A4', 1, 1, 4, 'available', NULL, NULL),
(245, 4, '1B1', 1, 2, 1, 'available', NULL, NULL),
(246, 4, '1B2', 1, 2, 2, 'available', NULL, NULL),
(247, 4, '1B3', 1, 2, 3, 'available', NULL, NULL),
(248, 4, '1B4', 1, 2, 4, 'available', NULL, NULL),
(249, 4, '1C1', 1, 3, 1, 'available', NULL, NULL),
(250, 4, '1C2', 1, 3, 2, 'available', NULL, NULL),
(251, 4, '1C3', 1, 3, 3, 'available', NULL, NULL),
(252, 4, '1C4', 1, 3, 4, 'available', NULL, NULL),
(253, 4, '1D1', 1, 4, 1, 'available', NULL, NULL),
(254, 4, '1D2', 1, 4, 2, 'available', NULL, NULL),
(255, 4, '1D3', 1, 4, 3, 'available', NULL, NULL),
(256, 4, '1D4', 1, 4, 4, 'available', NULL, NULL),
(257, 4, '1E1', 1, 5, 1, 'available', NULL, NULL),
(258, 4, '1E2', 1, 5, 2, 'available', NULL, NULL),
(259, 4, '1E3', 1, 5, 3, 'available', NULL, NULL),
(260, 4, '1E4', 1, 5, 4, 'available', NULL, NULL),
(261, 4, '2A1', 2, 1, 1, 'available', NULL, NULL),
(262, 4, '2A2', 2, 1, 2, 'available', NULL, NULL),
(263, 4, '2A3', 2, 1, 3, 'available', NULL, NULL),
(264, 4, '2A4', 2, 1, 4, 'available', NULL, NULL),
(265, 4, '2B1', 2, 2, 1, 'available', NULL, NULL),
(266, 4, '2B2', 2, 2, 2, 'available', NULL, NULL),
(267, 4, '2B3', 2, 2, 3, 'available', NULL, NULL),
(268, 4, '2B4', 2, 2, 4, 'available', NULL, NULL),
(269, 4, '2C1', 2, 3, 1, 'available', NULL, NULL),
(270, 4, '2C2', 2, 3, 2, 'available', NULL, NULL),
(271, 4, '2C3', 2, 3, 3, 'available', NULL, NULL),
(272, 4, '2C4', 2, 3, 4, 'available', NULL, NULL),
(273, 4, '2D1', 2, 4, 1, 'available', NULL, NULL),
(274, 4, '2D2', 2, 4, 2, 'available', NULL, NULL),
(275, 4, '2D3', 2, 4, 3, 'available', NULL, NULL),
(276, 4, '2D4', 2, 4, 4, 'available', NULL, NULL),
(277, 4, '2E1', 2, 5, 1, 'available', NULL, NULL),
(278, 4, '2E2', 2, 5, 2, 'available', NULL, NULL),
(279, 4, '2E3', 2, 5, 3, 'available', NULL, NULL),
(280, 4, '2E4', 2, 5, 4, 'available', NULL, NULL),
(281, 4, '3A1', 3, 1, 1, 'available', NULL, NULL),
(282, 4, '3A2', 3, 1, 2, 'available', NULL, NULL),
(283, 4, '3A3', 3, 1, 3, 'available', NULL, NULL),
(284, 4, '3A4', 3, 1, 4, 'available', NULL, NULL),
(285, 4, '3B1', 3, 2, 1, 'available', NULL, NULL),
(286, 4, '3B2', 3, 2, 2, 'available', NULL, NULL),
(287, 4, '3B3', 3, 2, 3, 'available', NULL, NULL),
(288, 4, '3B4', 3, 2, 4, 'available', NULL, NULL),
(289, 4, '3C1', 3, 3, 1, 'available', NULL, NULL),
(290, 4, '3C2', 3, 3, 2, 'available', NULL, NULL),
(291, 4, '3C3', 3, 3, 3, 'available', NULL, NULL),
(292, 4, '3C4', 3, 3, 4, 'available', NULL, NULL),
(293, 4, '3D1', 3, 4, 1, 'available', NULL, NULL),
(294, 4, '3D2', 3, 4, 2, 'available', NULL, NULL),
(295, 4, '3D3', 3, 4, 3, 'available', NULL, NULL),
(296, 4, '3D4', 3, 4, 4, 'available', NULL, NULL),
(297, 4, '3E1', 3, 5, 1, 'available', NULL, NULL),
(298, 4, '3E2', 3, 5, 2, 'available', NULL, NULL),
(299, 4, '3E3', 3, 5, 3, 'available', NULL, NULL),
(300, 4, '3E4', 3, 5, 4, 'available', NULL, NULL),
(301, 4, '4A1', 4, 1, 1, 'available', NULL, NULL),
(302, 4, '4A2', 4, 1, 2, 'available', NULL, NULL),
(303, 4, '4A3', 4, 1, 3, 'available', NULL, NULL),
(304, 4, '4A4', 4, 1, 4, 'available', NULL, NULL),
(305, 4, '4B1', 4, 2, 1, 'available', NULL, NULL),
(306, 4, '4B2', 4, 2, 2, 'available', NULL, NULL),
(307, 4, '4B3', 4, 2, 3, 'available', NULL, NULL),
(308, 4, '4B4', 4, 2, 4, 'available', NULL, NULL),
(309, 4, '4C1', 4, 3, 1, 'available', NULL, NULL),
(310, 4, '4C2', 4, 3, 2, 'available', NULL, NULL),
(311, 4, '4C3', 4, 3, 3, 'available', NULL, NULL),
(312, 4, '4C4', 4, 3, 4, 'available', NULL, NULL),
(313, 4, '4D1', 4, 4, 1, 'available', NULL, NULL),
(314, 4, '4D2', 4, 4, 2, 'available', NULL, NULL),
(315, 4, '4D3', 4, 4, 3, 'available', NULL, NULL),
(316, 4, '4D4', 4, 4, 4, 'available', NULL, NULL),
(317, 4, '4E1', 4, 5, 1, 'available', NULL, NULL),
(318, 4, '4E2', 4, 5, 2, 'available', NULL, NULL),
(319, 4, '4E3', 4, 5, 3, 'available', NULL, NULL),
(320, 4, '4E4', 4, 5, 4, 'available', NULL, NULL),
(321, 5, '1A1', 1, 1, 1, 'available', NULL, NULL),
(322, 5, '1A2', 1, 1, 2, 'available', NULL, NULL),
(323, 5, '1A3', 1, 1, 3, 'available', NULL, NULL),
(324, 5, '1A4', 1, 1, 4, 'available', NULL, NULL),
(325, 5, '1B1', 1, 2, 1, 'available', NULL, NULL),
(326, 5, '1B2', 1, 2, 2, 'available', NULL, NULL),
(327, 5, '1B3', 1, 2, 3, 'available', NULL, NULL),
(328, 5, '1B4', 1, 2, 4, 'available', NULL, NULL),
(329, 5, '1C1', 1, 3, 1, 'available', NULL, NULL),
(330, 5, '1C2', 1, 3, 2, 'available', NULL, NULL),
(331, 5, '1C3', 1, 3, 3, 'available', NULL, NULL),
(332, 5, '1C4', 1, 3, 4, 'available', NULL, NULL),
(333, 5, '1D1', 1, 4, 1, 'available', NULL, NULL),
(334, 5, '1D2', 1, 4, 2, 'available', NULL, NULL),
(335, 5, '1D3', 1, 4, 3, 'available', NULL, NULL),
(336, 5, '1D4', 1, 4, 4, 'available', NULL, NULL),
(337, 5, '1E1', 1, 5, 1, 'available', NULL, NULL),
(338, 5, '1E2', 1, 5, 2, 'available', NULL, NULL),
(339, 5, '1E3', 1, 5, 3, 'available', NULL, NULL),
(340, 5, '1E4', 1, 5, 4, 'available', NULL, NULL),
(341, 5, '2A1', 2, 1, 1, 'available', NULL, NULL),
(342, 5, '2A2', 2, 1, 2, 'available', NULL, NULL),
(343, 5, '2A3', 2, 1, 3, 'available', NULL, NULL),
(344, 5, '2A4', 2, 1, 4, 'available', NULL, NULL),
(345, 5, '2B1', 2, 2, 1, 'available', NULL, NULL),
(346, 5, '2B2', 2, 2, 2, 'available', NULL, NULL),
(347, 5, '2B3', 2, 2, 3, 'available', NULL, NULL),
(348, 5, '2B4', 2, 2, 4, 'available', NULL, NULL),
(349, 5, '2C1', 2, 3, 1, 'available', NULL, NULL),
(350, 5, '2C2', 2, 3, 2, 'available', NULL, NULL),
(351, 5, '2C3', 2, 3, 3, 'available', NULL, NULL),
(352, 5, '2C4', 2, 3, 4, 'available', NULL, NULL),
(353, 5, '2D1', 2, 4, 1, 'available', NULL, NULL),
(354, 5, '2D2', 2, 4, 2, 'available', NULL, NULL),
(355, 5, '2D3', 2, 4, 3, 'available', NULL, NULL),
(356, 5, '2D4', 2, 4, 4, 'available', NULL, NULL),
(357, 5, '2E1', 2, 5, 1, 'available', NULL, NULL),
(358, 5, '2E2', 2, 5, 2, 'available', NULL, NULL),
(359, 5, '2E3', 2, 5, 3, 'available', NULL, NULL),
(360, 5, '2E4', 2, 5, 4, 'available', NULL, NULL),
(361, 5, '3A1', 3, 1, 1, 'available', NULL, NULL),
(362, 5, '3A2', 3, 1, 2, 'available', NULL, NULL),
(363, 5, '3A3', 3, 1, 3, 'available', NULL, NULL),
(364, 5, '3A4', 3, 1, 4, 'available', NULL, NULL),
(365, 5, '3B1', 3, 2, 1, 'available', NULL, NULL),
(366, 5, '3B2', 3, 2, 2, 'available', NULL, NULL),
(367, 5, '3B3', 3, 2, 3, 'available', NULL, NULL),
(368, 5, '3B4', 3, 2, 4, 'available', NULL, NULL),
(369, 5, '3C1', 3, 3, 1, 'available', NULL, NULL),
(370, 5, '3C2', 3, 3, 2, 'available', NULL, NULL),
(371, 5, '3C3', 3, 3, 3, 'available', NULL, NULL),
(372, 5, '3C4', 3, 3, 4, 'available', NULL, NULL),
(373, 5, '3D1', 3, 4, 1, 'available', NULL, NULL),
(374, 5, '3D2', 3, 4, 2, 'available', NULL, NULL),
(375, 5, '3D3', 3, 4, 3, 'available', NULL, NULL),
(376, 5, '3D4', 3, 4, 4, 'available', NULL, NULL),
(377, 5, '3E1', 3, 5, 1, 'available', NULL, NULL),
(378, 5, '3E2', 3, 5, 2, 'available', NULL, NULL),
(379, 5, '3E3', 3, 5, 3, 'available', NULL, NULL),
(380, 5, '3E4', 3, 5, 4, 'available', NULL, NULL),
(381, 5, '4A1', 4, 1, 1, 'available', NULL, NULL),
(382, 5, '4A2', 4, 1, 2, 'available', NULL, NULL),
(383, 5, '4A3', 4, 1, 3, 'available', NULL, NULL),
(384, 5, '4A4', 4, 1, 4, 'available', NULL, NULL),
(385, 5, '4B1', 4, 2, 1, 'available', NULL, NULL),
(386, 5, '4B2', 4, 2, 2, 'available', NULL, NULL),
(387, 5, '4B3', 4, 2, 3, 'available', NULL, NULL),
(388, 5, '4B4', 4, 2, 4, 'available', NULL, NULL),
(389, 5, '4C1', 4, 3, 1, 'available', NULL, NULL),
(390, 5, '4C2', 4, 3, 2, 'available', NULL, NULL),
(391, 5, '4C3', 4, 3, 3, 'available', NULL, NULL),
(392, 5, '4C4', 4, 3, 4, 'available', NULL, NULL),
(393, 5, '4D1', 4, 4, 1, 'available', NULL, NULL),
(394, 5, '4D2', 4, 4, 2, 'available', NULL, NULL),
(395, 5, '4D3', 4, 4, 3, 'available', NULL, NULL),
(396, 5, '4D4', 4, 4, 4, 'available', NULL, NULL),
(397, 5, '4E1', 4, 5, 1, 'available', NULL, NULL),
(398, 5, '4E2', 4, 5, 2, 'available', NULL, NULL),
(399, 5, '4E3', 4, 5, 3, 'available', NULL, NULL),
(400, 5, '4E4', 4, 5, 4, 'available', NULL, NULL),
(401, 6, '1A1', 1, 1, 1, 'available', NULL, NULL),
(402, 6, '1A2', 1, 1, 2, 'available', NULL, NULL),
(403, 6, '1A3', 1, 1, 3, 'available', NULL, NULL),
(404, 6, '1A4', 1, 1, 4, 'available', NULL, NULL),
(405, 6, '1B1', 1, 2, 1, 'available', NULL, NULL),
(406, 6, '1B2', 1, 2, 2, 'available', NULL, NULL),
(407, 6, '1B3', 1, 2, 3, 'available', NULL, NULL),
(408, 6, '1B4', 1, 2, 4, 'available', NULL, NULL),
(409, 6, '1C1', 1, 3, 1, 'available', NULL, NULL),
(410, 6, '1C2', 1, 3, 2, 'available', NULL, NULL),
(411, 6, '1C3', 1, 3, 3, 'available', NULL, NULL),
(412, 6, '1C4', 1, 3, 4, 'available', NULL, NULL),
(413, 6, '1D1', 1, 4, 1, 'available', NULL, NULL),
(414, 6, '1D2', 1, 4, 2, 'available', NULL, NULL),
(415, 6, '1D3', 1, 4, 3, 'available', NULL, NULL),
(416, 6, '1D4', 1, 4, 4, 'available', NULL, NULL),
(417, 6, '1E1', 1, 5, 1, 'available', NULL, NULL),
(418, 6, '1E2', 1, 5, 2, 'available', NULL, NULL),
(419, 6, '1E3', 1, 5, 3, 'available', NULL, NULL),
(420, 6, '1E4', 1, 5, 4, 'available', NULL, NULL),
(421, 6, '2A1', 2, 1, 1, 'available', NULL, NULL),
(422, 6, '2A2', 2, 1, 2, 'available', NULL, NULL),
(423, 6, '2A3', 2, 1, 3, 'available', NULL, NULL),
(424, 6, '2A4', 2, 1, 4, 'available', NULL, NULL),
(425, 6, '2B1', 2, 2, 1, 'available', NULL, NULL),
(426, 6, '2B2', 2, 2, 2, 'available', NULL, NULL),
(427, 6, '2B3', 2, 2, 3, 'available', NULL, NULL),
(428, 6, '2B4', 2, 2, 4, 'available', NULL, NULL),
(429, 6, '2C1', 2, 3, 1, 'available', NULL, NULL),
(430, 6, '2C2', 2, 3, 2, 'available', NULL, NULL),
(431, 6, '2C3', 2, 3, 3, 'available', NULL, NULL),
(432, 6, '2C4', 2, 3, 4, 'available', NULL, NULL),
(433, 6, '2D1', 2, 4, 1, 'available', NULL, NULL),
(434, 6, '2D2', 2, 4, 2, 'available', NULL, NULL),
(435, 6, '2D3', 2, 4, 3, 'available', NULL, NULL),
(436, 6, '2D4', 2, 4, 4, 'available', NULL, NULL),
(437, 6, '2E1', 2, 5, 1, 'available', NULL, NULL),
(438, 6, '2E2', 2, 5, 2, 'available', NULL, NULL),
(439, 6, '2E3', 2, 5, 3, 'available', NULL, NULL),
(440, 6, '2E4', 2, 5, 4, 'available', NULL, NULL),
(441, 6, '3A1', 3, 1, 1, 'available', NULL, NULL),
(442, 6, '3A2', 3, 1, 2, 'available', NULL, NULL),
(443, 6, '3A3', 3, 1, 3, 'available', NULL, NULL),
(444, 6, '3A4', 3, 1, 4, 'available', NULL, NULL),
(445, 6, '3B1', 3, 2, 1, 'available', NULL, NULL),
(446, 6, '3B2', 3, 2, 2, 'available', NULL, NULL),
(447, 6, '3B3', 3, 2, 3, 'available', NULL, NULL),
(448, 6, '3B4', 3, 2, 4, 'available', NULL, NULL),
(449, 6, '3C1', 3, 3, 1, 'available', NULL, NULL),
(450, 6, '3C2', 3, 3, 2, 'available', NULL, NULL),
(451, 6, '3C3', 3, 3, 3, 'available', NULL, NULL),
(452, 6, '3C4', 3, 3, 4, 'available', NULL, NULL),
(453, 6, '3D1', 3, 4, 1, 'available', NULL, NULL),
(454, 6, '3D2', 3, 4, 2, 'available', NULL, NULL),
(455, 6, '3D3', 3, 4, 3, 'available', NULL, NULL),
(456, 6, '3D4', 3, 4, 4, 'available', NULL, NULL),
(457, 6, '3E1', 3, 5, 1, 'available', NULL, NULL),
(458, 6, '3E2', 3, 5, 2, 'available', NULL, NULL),
(459, 6, '3E3', 3, 5, 3, 'available', NULL, NULL),
(460, 6, '3E4', 3, 5, 4, 'available', NULL, NULL),
(461, 6, '4A1', 4, 1, 1, 'available', NULL, NULL),
(462, 6, '4A2', 4, 1, 2, 'available', NULL, NULL),
(463, 6, '4A3', 4, 1, 3, 'available', NULL, NULL),
(464, 6, '4A4', 4, 1, 4, 'available', NULL, NULL),
(465, 6, '4B1', 4, 2, 1, 'available', NULL, NULL),
(466, 6, '4B2', 4, 2, 2, 'available', NULL, NULL),
(467, 6, '4B3', 4, 2, 3, 'available', NULL, NULL),
(468, 6, '4B4', 4, 2, 4, 'available', NULL, NULL),
(469, 6, '4C1', 4, 3, 1, 'available', NULL, NULL),
(470, 6, '4C2', 4, 3, 2, 'available', NULL, NULL),
(471, 6, '4C3', 4, 3, 3, 'available', NULL, NULL),
(472, 6, '4C4', 4, 3, 4, 'available', NULL, NULL),
(473, 6, '4D1', 4, 4, 1, 'available', NULL, NULL),
(474, 6, '4D2', 4, 4, 2, 'available', NULL, NULL),
(475, 6, '4D3', 4, 4, 3, 'available', NULL, NULL),
(476, 6, '4D4', 4, 4, 4, 'available', NULL, NULL),
(477, 6, '4E1', 4, 5, 1, 'available', NULL, NULL),
(478, 6, '4E2', 4, 5, 2, 'available', NULL, NULL),
(479, 6, '4E3', 4, 5, 3, 'available', NULL, NULL),
(480, 6, '4E4', 4, 5, 4, 'available', NULL, NULL),
(481, 7, '1A1', 1, 1, 1, 'available', NULL, NULL),
(482, 7, '1A2', 1, 1, 2, 'available', NULL, NULL),
(483, 7, '1A3', 1, 1, 3, 'available', NULL, NULL),
(484, 7, '1A4', 1, 1, 4, 'available', NULL, NULL),
(485, 7, '1B1', 1, 2, 1, 'available', NULL, NULL),
(486, 7, '1B2', 1, 2, 2, 'available', NULL, NULL),
(487, 7, '1B3', 1, 2, 3, 'available', NULL, NULL),
(488, 7, '1B4', 1, 2, 4, 'available', NULL, NULL),
(489, 7, '1C1', 1, 3, 1, 'available', NULL, NULL),
(490, 7, '1C2', 1, 3, 2, 'available', NULL, NULL),
(491, 7, '1C3', 1, 3, 3, 'available', NULL, NULL),
(492, 7, '1C4', 1, 3, 4, 'available', NULL, NULL),
(493, 7, '1D1', 1, 4, 1, 'available', NULL, NULL),
(494, 7, '1D2', 1, 4, 2, 'available', NULL, NULL),
(495, 7, '1D3', 1, 4, 3, 'available', NULL, NULL),
(496, 7, '1D4', 1, 4, 4, 'available', NULL, NULL),
(497, 7, '1E1', 1, 5, 1, 'available', NULL, NULL),
(498, 7, '1E2', 1, 5, 2, 'available', NULL, NULL),
(499, 7, '1E3', 1, 5, 3, 'available', NULL, NULL),
(500, 7, '1E4', 1, 5, 4, 'available', NULL, NULL),
(501, 7, '2A1', 2, 1, 1, 'available', NULL, NULL),
(502, 7, '2A2', 2, 1, 2, 'available', NULL, NULL),
(503, 7, '2A3', 2, 1, 3, 'available', NULL, NULL),
(504, 7, '2A4', 2, 1, 4, 'available', NULL, NULL),
(505, 7, '2B1', 2, 2, 1, 'available', NULL, NULL),
(506, 7, '2B2', 2, 2, 2, 'available', NULL, NULL),
(507, 7, '2B3', 2, 2, 3, 'available', NULL, NULL),
(508, 7, '2B4', 2, 2, 4, 'available', NULL, NULL),
(509, 7, '2C1', 2, 3, 1, 'available', NULL, NULL),
(510, 7, '2C2', 2, 3, 2, 'available', NULL, NULL),
(511, 7, '2C3', 2, 3, 3, 'available', NULL, NULL),
(512, 7, '2C4', 2, 3, 4, 'available', NULL, NULL),
(513, 7, '2D1', 2, 4, 1, 'available', NULL, NULL),
(514, 7, '2D2', 2, 4, 2, 'available', NULL, NULL),
(515, 7, '2D3', 2, 4, 3, 'available', NULL, NULL),
(516, 7, '2D4', 2, 4, 4, 'available', NULL, NULL),
(517, 7, '2E1', 2, 5, 1, 'available', NULL, NULL),
(518, 7, '2E2', 2, 5, 2, 'available', NULL, NULL),
(519, 7, '2E3', 2, 5, 3, 'available', NULL, NULL),
(520, 7, '2E4', 2, 5, 4, 'available', NULL, NULL),
(521, 7, '3A1', 3, 1, 1, 'available', NULL, NULL),
(522, 7, '3A2', 3, 1, 2, 'available', NULL, NULL),
(523, 7, '3A3', 3, 1, 3, 'available', NULL, NULL),
(524, 7, '3A4', 3, 1, 4, 'available', NULL, NULL),
(525, 7, '3B1', 3, 2, 1, 'available', NULL, NULL),
(526, 7, '3B2', 3, 2, 2, 'available', NULL, NULL),
(527, 7, '3B3', 3, 2, 3, 'available', NULL, NULL),
(528, 7, '3B4', 3, 2, 4, 'available', NULL, NULL),
(529, 7, '3C1', 3, 3, 1, 'available', NULL, NULL),
(530, 7, '3C2', 3, 3, 2, 'available', NULL, NULL),
(531, 7, '3C3', 3, 3, 3, 'available', NULL, NULL),
(532, 7, '3C4', 3, 3, 4, 'available', NULL, NULL),
(533, 7, '3D1', 3, 4, 1, 'available', NULL, NULL),
(534, 7, '3D2', 3, 4, 2, 'available', NULL, NULL),
(535, 7, '3D3', 3, 4, 3, 'available', NULL, NULL),
(536, 7, '3D4', 3, 4, 4, 'available', NULL, NULL),
(537, 7, '3E1', 3, 5, 1, 'available', NULL, NULL),
(538, 7, '3E2', 3, 5, 2, 'available', NULL, NULL),
(539, 7, '3E3', 3, 5, 3, 'available', NULL, NULL),
(540, 7, '3E4', 3, 5, 4, 'available', NULL, NULL),
(541, 7, '4A1', 4, 1, 1, 'available', NULL, NULL),
(542, 7, '4A2', 4, 1, 2, 'available', NULL, NULL),
(543, 7, '4A3', 4, 1, 3, 'available', NULL, NULL),
(544, 7, '4A4', 4, 1, 4, 'available', NULL, NULL),
(545, 7, '4B1', 4, 2, 1, 'available', NULL, NULL),
(546, 7, '4B2', 4, 2, 2, 'available', NULL, NULL),
(547, 7, '4B3', 4, 2, 3, 'available', NULL, NULL),
(548, 7, '4B4', 4, 2, 4, 'available', NULL, NULL),
(549, 7, '4C1', 4, 3, 1, 'sold', 3, '2026-02-03 14:18:02'),
(550, 7, '4C2', 4, 3, 2, 'available', NULL, NULL),
(551, 7, '4C3', 4, 3, 3, 'available', NULL, NULL),
(552, 7, '4C4', 4, 3, 4, 'available', NULL, NULL),
(553, 7, '4D1', 4, 4, 1, 'available', NULL, NULL),
(554, 7, '4D2', 4, 4, 2, 'available', NULL, NULL),
(555, 7, '4D3', 4, 4, 3, 'available', NULL, NULL),
(556, 7, '4D4', 4, 4, 4, 'available', NULL, NULL),
(557, 7, '4E1', 4, 5, 1, 'available', NULL, NULL),
(558, 7, '4E2', 4, 5, 2, 'available', NULL, NULL),
(559, 7, '4E3', 4, 5, 3, 'available', NULL, NULL),
(560, 7, '4E4', 4, 5, 4, 'available', NULL, NULL),
(561, 8, '1A1', 1, 1, 1, 'available', NULL, NULL),
(562, 8, '1A2', 1, 1, 2, 'available', NULL, NULL),
(563, 8, '1A3', 1, 1, 3, 'available', NULL, NULL),
(564, 8, '1A4', 1, 1, 4, 'available', NULL, NULL),
(565, 8, '1B1', 1, 2, 1, 'available', NULL, NULL),
(566, 8, '1B2', 1, 2, 2, 'available', NULL, NULL),
(567, 8, '1B3', 1, 2, 3, 'available', NULL, NULL),
(568, 8, '1B4', 1, 2, 4, 'available', NULL, NULL),
(569, 8, '1C1', 1, 3, 1, 'available', NULL, NULL),
(570, 8, '1C2', 1, 3, 2, 'available', NULL, NULL),
(571, 8, '1C3', 1, 3, 3, 'available', NULL, NULL),
(572, 8, '1C4', 1, 3, 4, 'available', NULL, NULL),
(573, 8, '1D1', 1, 4, 1, 'available', NULL, NULL),
(574, 8, '1D2', 1, 4, 2, 'available', NULL, NULL),
(575, 8, '1D3', 1, 4, 3, 'available', NULL, NULL),
(576, 8, '1D4', 1, 4, 4, 'available', NULL, NULL),
(577, 8, '1E1', 1, 5, 1, 'available', NULL, NULL),
(578, 8, '1E2', 1, 5, 2, 'available', NULL, NULL),
(579, 8, '1E3', 1, 5, 3, 'available', NULL, NULL),
(580, 8, '1E4', 1, 5, 4, 'available', NULL, NULL),
(581, 8, '2A1', 2, 1, 1, 'available', NULL, NULL),
(582, 8, '2A2', 2, 1, 2, 'available', NULL, NULL),
(583, 8, '2A3', 2, 1, 3, 'available', NULL, NULL),
(584, 8, '2A4', 2, 1, 4, 'available', NULL, NULL),
(585, 8, '2B1', 2, 2, 1, 'available', NULL, NULL),
(586, 8, '2B2', 2, 2, 2, 'available', NULL, NULL),
(587, 8, '2B3', 2, 2, 3, 'available', NULL, NULL),
(588, 8, '2B4', 2, 2, 4, 'available', NULL, NULL),
(589, 8, '2C1', 2, 3, 1, 'available', NULL, NULL),
(590, 8, '2C2', 2, 3, 2, 'available', NULL, NULL),
(591, 8, '2C3', 2, 3, 3, 'available', NULL, NULL),
(592, 8, '2C4', 2, 3, 4, 'available', NULL, NULL),
(593, 8, '2D1', 2, 4, 1, 'available', NULL, NULL),
(594, 8, '2D2', 2, 4, 2, 'available', NULL, NULL),
(595, 8, '2D3', 2, 4, 3, 'available', NULL, NULL),
(596, 8, '2D4', 2, 4, 4, 'available', NULL, NULL),
(597, 8, '2E1', 2, 5, 1, 'available', NULL, NULL),
(598, 8, '2E2', 2, 5, 2, 'available', NULL, NULL),
(599, 8, '2E3', 2, 5, 3, 'available', NULL, NULL),
(600, 8, '2E4', 2, 5, 4, 'available', NULL, NULL),
(601, 8, '3A1', 3, 1, 1, 'available', NULL, NULL),
(602, 8, '3A2', 3, 1, 2, 'available', NULL, NULL),
(603, 8, '3A3', 3, 1, 3, 'available', NULL, NULL),
(604, 8, '3A4', 3, 1, 4, 'available', NULL, NULL),
(605, 8, '3B1', 3, 2, 1, 'available', NULL, NULL),
(606, 8, '3B2', 3, 2, 2, 'available', NULL, NULL),
(607, 8, '3B3', 3, 2, 3, 'available', NULL, NULL),
(608, 8, '3B4', 3, 2, 4, 'available', NULL, NULL),
(609, 8, '3C1', 3, 3, 1, 'available', NULL, NULL),
(610, 8, '3C2', 3, 3, 2, 'available', NULL, NULL),
(611, 8, '3C3', 3, 3, 3, 'available', NULL, NULL),
(612, 8, '3C4', 3, 3, 4, 'available', NULL, NULL),
(613, 8, '3D1', 3, 4, 1, 'available', NULL, NULL),
(614, 8, '3D2', 3, 4, 2, 'available', NULL, NULL),
(615, 8, '3D3', 3, 4, 3, 'available', NULL, NULL),
(616, 8, '3D4', 3, 4, 4, 'available', NULL, NULL),
(617, 8, '3E1', 3, 5, 1, 'available', NULL, NULL),
(618, 8, '3E2', 3, 5, 2, 'available', NULL, NULL),
(619, 8, '3E3', 3, 5, 3, 'available', NULL, NULL),
(620, 8, '3E4', 3, 5, 4, 'available', NULL, NULL),
(621, 8, '4A1', 4, 1, 1, 'available', NULL, NULL),
(622, 8, '4A2', 4, 1, 2, 'available', NULL, NULL),
(623, 8, '4A3', 4, 1, 3, 'available', NULL, NULL),
(624, 8, '4A4', 4, 1, 4, 'available', NULL, NULL),
(625, 8, '4B1', 4, 2, 1, 'available', NULL, NULL),
(626, 8, '4B2', 4, 2, 2, 'available', NULL, NULL),
(627, 8, '4B3', 4, 2, 3, 'available', NULL, NULL),
(628, 8, '4B4', 4, 2, 4, 'available', NULL, NULL),
(629, 8, '4C1', 4, 3, 1, 'available', NULL, NULL),
(630, 8, '4C2', 4, 3, 2, 'available', NULL, NULL),
(631, 8, '4C3', 4, 3, 3, 'available', NULL, NULL),
(632, 8, '4C4', 4, 3, 4, 'available', NULL, NULL),
(633, 8, '4D1', 4, 4, 1, 'available', NULL, NULL),
(634, 8, '4D2', 4, 4, 2, 'available', NULL, NULL),
(635, 8, '4D3', 4, 4, 3, 'available', NULL, NULL),
(636, 8, '4D4', 4, 4, 4, 'available', NULL, NULL),
(637, 8, '4E1', 4, 5, 1, 'available', NULL, NULL),
(638, 8, '4E2', 4, 5, 2, 'available', NULL, NULL),
(639, 8, '4E3', 4, 5, 3, 'available', NULL, NULL),
(640, 8, '4E4', 4, 5, 4, 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- Структура таблицы `sessions`
--

CREATE TABLE `sessions` (
  `id` int NOT NULL,
  `movie_id` int NOT NULL,
  `start_time` datetime NOT NULL,
  `hall_number` int DEFAULT '1',
  `base_price` decimal(8,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `sessions`
--

INSERT INTO `sessions` (`id`, `movie_id`, `start_time`, `hall_number`, `base_price`) VALUES
(1, 1, '2026-02-04 23:23:40', 1, 350.00),
(2, 1, '2026-02-05 03:23:40', 1, 400.00),
(3, 2, '2026-02-05 00:23:40', 1, 300.00),
(4, 2, '2026-02-03 07:23:00', 1, 450.00),
(5, 3, '2026-02-04 01:23:40', 1, 350.00),
(6, 3, '2026-02-04 05:23:40', 1, 400.00),
(7, 4, '2026-02-04 03:23:40', 1, 320.00),
(8, 4, '2026-02-04 09:23:40', 1, 500.00);

--
-- Триггеры `sessions`
--
DELIMITER $$
CREATE TRIGGER `after_session_insert` AFTER INSERT ON `sessions` FOR EACH ROW BEGIN
    CALL GenerateSeatsForSession(NEW.id);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Структура таблицы `tickets`
--

CREATE TABLE `tickets` (
  `id` int NOT NULL,
  `ticket_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `session_id` int NOT NULL,
  `seat_id` int NOT NULL,
  `price` decimal(8,2) NOT NULL,
  `ticket_status` enum('active','cancelled','expired','used') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `purchase_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `qr_code_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_number`, `user_id`, `session_id`, `seat_id`, `price`, `ticket_status`, `purchase_time`, `qr_code_path`) VALUES
(1, 'TCK-20260203-275223', 3, 7, 549, 320.00, 'active', '2026-02-03 09:03:15', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLTI3NTIyMw0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA='),
(2, 'TCK-20260203-01C401', 3, 3, 161, 300.00, 'active', '2026-02-03 09:54:48', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLTAxQzQwMQ0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA='),
(3, 'TCK-20260203-69603C', 3, 3, 162, 300.00, 'active', '2026-02-03 09:54:48', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLTY5NjAzQw0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA='),
(4, 'TCK-20260203-AE5F3C', 3, 3, 163, 300.00, 'active', '2026-02-03 09:54:48', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLUFFNUYzQw0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA='),
(5, 'TCK-20260203-74ABFE', 3, 3, 164, 300.00, 'active', '2026-02-03 09:54:48', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLTc0QUJGRQ0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA='),
(6, 'TCK-20260203-F21C6D', 3, 3, 165, 300.00, 'active', '2026-02-03 09:54:48', 'data:image/svg+xml;base64,DQogICAgICAgIDxzdmcgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCI+DQogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZmZmZmZmIi8+DQogICAgICAgICAgICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iIGZpbGw9IiMwMDAwMDAiIGZvbnQtZmFtaWx5PSJBcmlhbCI+DQogICAgICAgICAgICAgICAgVENLLTIwMjYwMjAzLUYyMUM2RA0KICAgICAgICAgICAgPC90ZXh0Pg0KICAgICAgICA8L3N2Zz4NCiAgICA=');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `full_name`, `role`, `created_at`) VALUES
(3, 't0lstikovalera@yandex.ru', '$2y$10$8hWHCBKoNnux4QWYBcB1EeBhM4DJCiH7XehcBOS4WPW4TmoRI7zXS', 'Толстикова Валерия Александровна', 'user', '2026-02-03 08:48:45'),
(4, 'admin@admin.com', '$2y$10$2QKIm5C56eAl7BSnrASHPeR.VEedq0epALq73FJD2hWLEqmxVXBX6', 'admin', 'admin', '2026-02-03 09:34:44'),
(5, 'user@user.com', '$2y$10$pa0BbwyvKR89Zf1w6NODoOheLD.QzI6gwEktN6LLoLJGiDl.JHryK', 'user', 'user', '2026-02-03 09:55:56');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `movies`
--
ALTER TABLE `movies`
  ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `seats`
--
ALTER TABLE `seats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_seat_session` (`session_id`,`seat_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `movie_id` (`movie_id`),
  ADD KEY `idx_start_time` (`start_time`);

--
-- Индексы таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `seat_id` (`seat_id`),
  ADD KEY `idx_ticket_number` (`ticket_number`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `movies`
--
ALTER TABLE `movies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `seats`
--
ALTER TABLE `seats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=641;

--
-- AUTO_INCREMENT для таблицы `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT для таблицы `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `seats_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ограничения внешнего ключа таблицы `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movies` (`id`) ON DELETE CASCADE;

--
-- Ограничения внешнего ключа таблицы `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
