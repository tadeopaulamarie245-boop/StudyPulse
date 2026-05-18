<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudyPulse</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --bg: #eef4ff;
    --surface: #ffffff;
    --surface-strong: #f8fbff;
    --text: #1f2937;
    --muted: #6b7280;
    --primary: #4f46e5;
    --primary-soft: #e0e7ff;
    --success: #10b981;
    --danger: #ef4444;
    --border: rgba(148, 163, 184, 0.24);
    --shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif, "Inter";
}

html {
    scroll-behavior: smooth;
}

body {
    min-height: 100vh;
    background: linear-gradient(180deg, #f7f9ff 0%, #eef4ff 100%);
    color: var(--text);
    display: flex;
}

.sidebar {
    width: 260px;
    min-height: 100vh;
    background: #ffffff;
    border-right: 1px solid rgba(148, 163, 184, 0.16);
    padding: 28px 22px;
    box-shadow: 4px 0 30px rgba(15, 23, 42, 0.08);
}

.sidebar h2 {
    font-size: 22px;
    color: #3730a3;
    letter-spacing: 0.06em;
    margin-bottom: 28px;
}

.sidebar nav {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.sidebar a {
    display: block;
    padding: 14px 16px;
    border-radius: 14px;
    color: #334155;
    text-decoration: none;
    transition: all 0.2s ease;
    font-weight: 600;
    background: rgba(99, 102, 241, 0.05);
}

.sidebar a:hover {
    background: rgba(99, 102, 241, 0.14);
    transform: translateX(3px);
    color: #1f2937;
}

.sidebar a.active {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    color: #3730a3;
    box-shadow: inset 0 0 0 1px rgba(99, 102, 241, 0.15);
}

.main {
    flex: 1;
    padding: 28px 32px;
    overflow-x: hidden;
}

.topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;
    margin-bottom: 30px;
}

.page-title {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: -0.02em;
    line-height: 1.1;
}

.topbar-meta {
    display: flex;
    gap: 14px;
    align-items: center;
}

.badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 999px;
    background: rgba(79, 70, 229, 0.08);
    color: #4338ca;
    font-weight: 600;
    font-size: 13px;
}

.cards {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 32px;
}

.card {
    background: var(--surface);
    border-radius: 24px;
    padding: 28px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
}

.card-title {
    color: var(--muted);
    font-size: 14px;
    margin-bottom: 12px;
    display: inline-block;
}

.card-value {
    font-size: 40px;
    font-weight: 700;
    color: var(--text);
}

.card-note {
    margin-top: 12px;
    color: var(--muted);
    font-size: 13px;
}

.box {
    background: var(--surface);
    border-radius: 24px;
    padding: 28px;
    border: 1px solid var(--border);
    box-shadow: var(--shadow);
    margin-top: 26px;
}

.box h3 {
    font-size: 22px;
    margin-bottom: 20px;
    color: #111827;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 680px;
}

th, td {
    padding: 18px 16px;
    text-align: left;
}

th {
    background: #f8fafc;
    color: #334155;
    font-weight: 700;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
}

td {
    background: #ffffff;
    color: #475569;
    border-bottom: 1px solid rgba(148, 163, 184, 0.12);
}

tr:hover td {
    background: #f8fbff;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 14px;
    background: linear-gradient(135deg, #4f46e5 0%, #818cf8 100%);
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 28px rgba(79, 70, 229, 0.18);
}

.btn-secondary {
    background: #f8fafc;
    color: #334155;
    border: 1px solid rgba(148, 163, 184, 0.3);
}

.input-group {
    display: grid;
    gap: 16px;
    margin-bottom: 22px;
}

input, textarea, select {
    width: 100%;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid #d1d5db;
    background: #f8fafc;
    color: #111827;
    font-size: 14px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.empty-state {
    padding: 24px;
    border-radius: 18px;
    border: 1px dashed rgba(148, 163, 184, 0.4);
    background: #f8fafc;
    text-align: center;
    color: #6b7280;
}

@media (max-width: 1100px) {
    .cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    body {
        flex-direction: column;
    }

    .sidebar {
        position: relative;
        width: 100%;
        height: auto;
    }

    .main {
        padding: 20px;
    }

    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .cards {
        grid-template-columns: 1fr;
    }

    table {
        min-width: 100%;
    }
}
</style>
</head>

<body>
