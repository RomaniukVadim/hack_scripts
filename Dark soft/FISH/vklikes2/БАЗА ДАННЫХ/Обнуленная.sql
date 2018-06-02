-- phpMyAdmin SQL Dump
-- version 4.2.10.1
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Авг 09 2015 г., 16:07
-- Версия сервера: 5.5.44-0+deb7u1
-- Версия PHP: 5.4.4-14+deb7u9

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `del123`
--

-- --------------------------------------------------------

--
-- Структура таблицы `tb_ads`
--

CREATE TABLE IF NOT EXISTS `tb_ads` (
`id` int(10) NOT NULL,
  `user` int(10) NOT NULL,
  `balans` int(6) NOT NULL,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `link` varchar(200) COLLATE utf8_unicode_ci NOT NULL,
  `link_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `for_one` int(5) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_ads_views`
--

CREATE TABLE IF NOT EXISTS `tb_ads_views` (
`id` int(10) NOT NULL,
  `user` int(10) NOT NULL,
  `ad_id` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `ad_type` varchar(10) COLLATE utf8_unicode_ci NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_events`
--

CREATE TABLE IF NOT EXISTS `tb_events` (
`id` int(10) NOT NULL,
  `uid` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `message` varchar(250) COLLATE utf8_unicode_ci NOT NULL,
  `time` int(10) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_members`
--

CREATE TABLE IF NOT EXISTS `tb_members` (
`id` int(10) NOT NULL,
  `uid` varchar(20) NOT NULL,
  `likes` int(6) NOT NULL DEFAULT '0',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00',
  `name` varchar(30) NOT NULL,
  `lastname` varchar(30) NOT NULL,
  `referer` varchar(20) NOT NULL,
  `refs` int(10) NOT NULL DEFAULT '0',
  `ban` int(1) NOT NULL DEFAULT '0',
  `bonus` int(10) NOT NULL DEFAULT '0'
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_news`
--

CREATE TABLE IF NOT EXISTS `tb_news` (
`id` int(10) NOT NULL,
  `data` varchar(10) NOT NULL,
  `nazv` varchar(150) NOT NULL,
  `newstext` text NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_pay`
--

CREATE TABLE IF NOT EXISTS `tb_pay` (
`id` int(10) NOT NULL,
  `uid` varchar(20) COLLATE utf8_unicode_ci NOT NULL,
  `likes` int(6) NOT NULL DEFAULT '0',
  `money` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `tb_pay_me`
--

CREATE TABLE IF NOT EXISTS `tb_pay_me` (
`id` int(10) NOT NULL,
  `uid` varchar(20) NOT NULL,
  `money` decimal(10,2) NOT NULL,
  `wmr` varchar(13) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `tb_ads`
--
ALTER TABLE `tb_ads`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_ads_views`
--
ALTER TABLE `tb_ads_views`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_events`
--
ALTER TABLE `tb_events`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_members`
--
ALTER TABLE `tb_members`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_news`
--
ALTER TABLE `tb_news`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_pay`
--
ALTER TABLE `tb_pay`
 ADD PRIMARY KEY (`id`);

--
-- Индексы таблицы `tb_pay_me`
--
ALTER TABLE `tb_pay_me`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `tb_ads`
--
ALTER TABLE `tb_ads`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_ads_views`
--
ALTER TABLE `tb_ads_views`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_events`
--
ALTER TABLE `tb_events`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_members`
--
ALTER TABLE `tb_members`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_news`
--
ALTER TABLE `tb_news`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_pay`
--
ALTER TABLE `tb_pay`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT для таблицы `tb_pay_me`
--
ALTER TABLE `tb_pay_me`
MODIFY `id` int(10) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
