-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Хост: 127.0.0.1
-- Время создания: Апр 29 2026 г., 23:34
-- Версия сервера: 10.4.32-MariaDB
-- Версия PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `shopbymax`
--

-- --------------------------------------------------------

--
-- Структура таблицы `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `cart`
--

INSERT INTO `cart` (`cart_id`, `user_id`, `created_at`) VALUES
(1, 2, '2026-04-29 17:05:30'),
(2, 5, '2026-04-29 17:26:17'),
(3, 1, '2026-04-29 18:12:13'),
(4, 6, '2026-04-29 18:26:13');

-- --------------------------------------------------------

--
-- Структура таблицы `cartitems`
--

CREATE TABLE `cartitems` (
  `cart_item_id` int(11) NOT NULL,
  `cart_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `cartitems`
--

INSERT INTO `cartitems` (`cart_item_id`, `cart_id`, `product_id`, `quantity`) VALUES
(7, 3, 8, 1),
(12, 3, 10, 1),
(13, 3, 1, 1);

-- --------------------------------------------------------

--
-- Структура таблицы `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `slug`, `created_at`) VALUES
(1, 'Наушники', 'naushniki', '2026-04-29 14:29:47'),
(2, 'Портативные колонки', 'portativnye-kolonki', '2026-04-29 14:29:47'),
(3, 'Усилители и ЦАП', 'usiliteli-i-cap', '2026-04-29 14:29:47'),
(4, 'Виниловые проигрыватели', 'vinilovye-proigryvateli', '2026-04-29 14:29:47'),
(5, 'Микрофоны', 'mikrofony', '2026-04-29 14:29:47'),
(6, 'Кабели и аксессуары', 'kabeli-i-aksessuary', '2026-04-29 14:29:47'),
(7, 'Сабвуферы', 'Сабвуферы', '2026-04-29 16:17:51');

-- --------------------------------------------------------

--
-- Структура таблицы `orderitems`
--

CREATE TABLE `orderitems` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `orderitems`
--

INSERT INTO `orderitems` (`order_item_id`, `order_id`, `product_id`, `quantity`, `price_at_purchase`) VALUES
(1, 1, 7, 1, 29990.00),
(2, 2, 5, 1, 8990.00),
(3, 3, 7, 2, 29990.00),
(4, 3, 6, 1, 2990.00),
(5, 3, 5, 2, 8990.00),
(6, 3, 4, 1, 11990.00),
(7, 4, 10, 1, 139999.00),
(8, 4, 9, 1, 17999.00),
(9, 4, 1, 1, 34990.00),
(10, 5, 4, 1, 11990.00);

-- --------------------------------------------------------

--
-- Структура таблицы `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `delivery_address` text DEFAULT NULL,
  `delivery_method` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `created_at`, `status`, `delivery_address`, `delivery_method`, `payment_method`) VALUES
(1, 2, 29990.00, '2026-04-29 17:05:47', 'processing', 'Богдано Хмельницкого д 4', 'pickup', 'card'),
(2, 5, 8990.00, '2026-04-29 17:26:31', 'cancelled', 'Богдано Хмельницкого д 4', 'courier', 'cash'),
(3, 5, 92940.00, '2026-04-29 17:38:29', 'completed', 'ffffeeee', 'post', 'online'),
(4, 5, 192988.00, '2026-04-29 18:20:46', 'processing', 'НА БААААЗУ', 'pickup', 'cash'),
(5, 6, 11990.00, '2026-04-29 18:26:46', 'completed', 'Ул. Пушкина. д. 1', 'courier', 'cash');

-- --------------------------------------------------------

--
-- Структура таблицы `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `photo` varchar(512) DEFAULT 'default.jpg',
  `created_at` datetime DEFAULT current_timestamp(),
  `category` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `products`
--

INSERT INTO `products` (`product_id`, `name`, `description`, `price`, `stock`, `photo`, `created_at`, `category`) VALUES
(1, 'Sony WH-1000XM5', 'Беспроводные наушники с активным шумоподавлением. Bluetooth 5.2, до 30 часов работы.', 34990.00, 9, 'https://c.dns-shop.ru/thumb/st4/fit/wm/0/0/da703c4ba95708d23fc83061b7f32c4e/2df2632475190df8324dd72376786ed737facef7dc387bf5fcd76f88d5fc28ec.jpg.webp', '2026-04-29 16:29:47', 'Наушники'),
(2, 'JBL Flip 6', 'Портативная колонка с чистым мощным звуком. Защита IP67, до 12 часов работы.', 8990.00, 20, 'https://ir.ozone.ru/s3/multimedia-c/6491215656.jpg', '2026-04-29 16:29:47', 'Портативные колонки'),
(3, 'Audio-Technica AT-LP60X', 'Автоматический виниловый проигрыватель со встроенным фонокорректором.', 15990.00, 5, 'https://avatars.mds.yandex.net/get-goods_pic/15429922/hatf8b89316ddc3f633a4315ec25bf5e028/orig', '2026-04-29 16:29:47', 'Виниловые проигрыватели'),
(4, 'FiiO K5 Pro', 'Усилитель для наушников и ЦАП с поддержкой аудио высокого разрешения.', 11990.00, 6, 'https://avatars.mds.yandex.net/get-goods_pic/16468752/hatf3b731fc9515e68ebef9be1877460d05/orig', '2026-04-29 16:29:47', 'Усилители и ЦАП'),
(5, 'Shure SM58', 'Легендарный динамический микрофон для вокала. Кардиоидная направленность.', 8990.00, 12, 'https://bxuiiaeu1l.a.trbcdn.net/4l0/2ec/oyy/zs4/8s8/4os/okw/0sg/s/4l02ecoyyzs48s84osokw0sgs.jpeg', '2026-04-29 16:29:47', 'Микрофоны'),
(6, 'AudioQuest Evergreen', 'Кабель 2xRCA – 2xRCA, длина 1.5 м. Медные проводники, двойное экранирование.', 2990.00, 29, 'https://proektor77.ru/img_db/img_thumb_big.php?id=15539', '2026-04-29 16:29:47', 'Кабели и аксессуары'),
(7, 'Bose QuietComfort 45', 'Наушники с шумоподавлением мирового уровня. До 24 часов работы.', 29990.00, 9, 'https://avatars.mds.yandex.net/get-goods_pic/13793475/hat1ebe9fa7d3d9e72da35f9ef713c743fd/orig', '2026-04-29 16:29:47', 'Наушники'),
(8, 'Marshall Emberton II', 'Компактная колонка с фирменным звуком. Защита IP67, до 30 часов работы.', 14990.00, 18, 'https://avatars.mds.yandex.net/i?id=05f5e3c15feb24e686aa6a6268699a82_l-12475834-images-thumbs&n=13&n=13&w=345&h=230', '2026-04-29 16:29:47', 'Портативные колонки'),
(9, 'Автосабвуфер активный DL Audio Piranha 15A V.3', 'Автосабвуфер активный DL Audio Piranha 15A V.3 получил динамик диаметром 15” и мощностью 1100 Вт. Он предназначен для устранения «плоского» звука в штатной магнитоле. Сабвуфер добавляет звучанию в салоне объем. Высокая линейность воспроизводит бас таким, каким он был записан, без гуда и хрипа даже на высокой громкости. Повышенная отдача «на низах» позволяет проигрывать глубокие частоты, которые создают вибрацию и ощущаются телом.\r\nАвтосабвуфер активный DL Audio Piranha 15A V.3 фазоинверторного типа использует энергию задней стороны диффузора для усиления звука. Такая модель увеличивает громкость почти в 2 раза, обеспечивает глубокие, мощные басы и дает возможность настройки частоты. Температуростойкая звуковая катушка быстро отводит тепло и предотвращает перегрев системы. В комплекте идут фирменные кабели и монтажный набор.', 17999.00, 14, 'https://c.dns-shop.ru/thumb/st1/fit/wm/0/0/62e04f13b0b72e66562f42776e96b40e/31b3f509009fec94d3fcb14bde6fd8b19db6ea71d01530f2eda3443dd0d47bc2.jpg.webp', '2026-04-29 18:18:35', 'Сабвуферы'),
(10, 'Apocalypse DB-455-NEO D1', 'Apocalypse DB-455-NEO D1 — это пассивный сабвуфер для автомобилей, разработанный для экстремальных нагрузок и соревнований по автозвуку. Он отличается высокой номинальной мощностью и использованием неодимового магнита.', 139999.00, 4, 'https://static5.alphardaudio.ru/media/subwoofers/apocalypse-db-455-neo-d1-d2/hJ9cruiQh.jpg?width=800&height=800', '2026-04-29 18:20:03', 'Сабвуферы');

-- --------------------------------------------------------

--
-- Структура таблицы `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `user_id`, `rating`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(1, 10, 5, 5, 'ЛУПИТ ТАК, ЧТО СТЕКЛА ВЫЛЕТАЮТ', 'approved', '2026-04-29 18:21:02', '2026-04-29 18:21:35'),
(2, 3, 5, 5, 'Самое то, чтобы послушать джазик', 'approved', '2026-04-29 18:21:22', '2026-04-29 18:21:35');

-- --------------------------------------------------------

--
-- Структура таблицы `review_photos`
--

CREATE TABLE `review_photos` (
  `photo_id` int(11) NOT NULL,
  `review_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_role` enum('admin','manager','user') DEFAULT 'user',
  `email` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `full_name` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `user_role`, `email`, `created_at`, `full_name`, `phone`, `address`) VALUES
(1, 'admin', '$2y$10$uX9ve9lHoHQd9MiYr4RTT.G/Te.Q44t.Dbrs/z3oRlh9GZLmiN0JW', 'admin', 'admin@shopbymax.ru', '2026-04-29 16:29:47', 'Администратор', NULL, NULL),
(2, 'test', '$2y$10$ickHf/mIp7wZl/CE9/N0j.uJQ96UbPTBxwY7eigV8lnaV2mysA33u', 'user', 'maksimkabukharov2345@gmail.com', '2026-04-29 17:05:19', 'Бухаров Максим Андреевич', '+79527940799', NULL),
(4, 'test1', '$2y$10$RnXWqSPlQ9eTjW6bry9OSu8lpcszOQYJg2LO6mkpdJJspnXkP/gn2', 'user', 'maksimkabukharov@gmail.com', '2026-04-29 17:25:13', 'Бухаров Максим Андреевич', '+79527940799', NULL),
(5, 'test22', '$2y$10$1qC681emNZwrGbi4CWEYd.hz7KO/Si9yqkKWB9cB4WKUC2n.uMqv2', 'user', 'maksimkabukharo@gmail.com', '2026-04-29 17:25:58', 'Бухаров Максим Андреевич', '+79527940799', NULL),
(6, 'Покупатель', '$2y$10$44oJK5NCKhOnwk0.uzs.TOY6thwHdojdn7zhr4XCWlAIWzv2eG3d2', 'user', 'pokypatel@yandex.ru', '2026-04-29 18:25:53', 'Бухаров Максим Андреевич', '+79527940799', NULL);

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `cartitems`
--
ALTER TABLE `cartitems`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Индексы таблицы `orderitems`
--
ALTER TABLE `orderitems`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Индексы таблицы `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`);

--
-- Индексы таблицы `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Индексы таблицы `review_photos`
--
ALTER TABLE `review_photos`
  ADD PRIMARY KEY (`photo_id`),
  ADD KEY `review_id` (`review_id`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT для таблицы `cartitems`
--
ALTER TABLE `cartitems`
  MODIFY `cart_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT для таблицы `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT для таблицы `orderitems`
--
ALTER TABLE `orderitems`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT для таблицы `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT для таблицы `review_photos`
--
ALTER TABLE `review_photos`
  MODIFY `photo_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
