-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 25/02/2025 às 02:34
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `agenda_ufpr`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agendas`
--

CREATE TABLE `agendas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `color` varchar(7) DEFAULT '#3788d8',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `public_hash` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `agendas`
--

INSERT INTO `agendas` (`id`, `user_id`, `title`, `description`, `is_public`, `color`, `created_at`, `updated_at`, `public_hash`) VALUES
(3, 1, 'teste', 'teste de agenda', 1, '#d73737', '2025-02-24 21:15:02', '2025-02-24 21:35:04', ''),
(4, 1, 'teste', 'teste123', 0, '#3788d8', '2025-02-24 21:29:12', '2025-02-24 21:29:12', '');

-- --------------------------------------------------------

--
-- Estrutura para tabela `agenda_shares`
--

CREATE TABLE `agenda_shares` (
  `id` int(11) NOT NULL,
  `agenda_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_edit` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `compromissos`
--

CREATE TABLE `compromissos` (
  `id` int(11) NOT NULL,
  `agenda_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `repeat_type` enum('none','daily','weekly','specific_days') DEFAULT 'none',
  `repeat_until` date DEFAULT NULL,
  `repeat_days` varchar(20) DEFAULT NULL,
  `status` enum('pendente','realizado','cancelado','aguardando_aprovacao') DEFAULT 'pendente',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `compromisso_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_access` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `username`, `name`, `email`, `first_access`, `created_at`, `updated_at`) VALUES
(1, 'teste', 'Usuário Teste', 'teste@example.com', 0, '2025-02-24 18:03:11', '2025-02-24 18:03:11');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agendas`
--
ALTER TABLE `agendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_public_hash` (`public_hash`);

--
-- Índices de tabela `agenda_shares`
--
ALTER TABLE `agenda_shares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_share` (`agenda_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `compromissos`
--
ALTER TABLE `compromissos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `agenda_id` (`agenda_id`);

--
-- Índices de tabela `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `compromisso_id` (`compromisso_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agendas`
--
ALTER TABLE `agendas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `agenda_shares`
--
ALTER TABLE `agenda_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `compromissos`
--
ALTER TABLE `compromissos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agendas`
--
ALTER TABLE `agendas`
  ADD CONSTRAINT `agendas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `agenda_shares`
--
ALTER TABLE `agenda_shares`
  ADD CONSTRAINT `agenda_shares_ibfk_1` FOREIGN KEY (`agenda_id`) REFERENCES `agendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `agenda_shares_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `compromissos`
--
ALTER TABLE `compromissos`
  ADD CONSTRAINT `compromissos_ibfk_1` FOREIGN KEY (`agenda_id`) REFERENCES `agendas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`compromisso_id`) REFERENCES `compromissos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
