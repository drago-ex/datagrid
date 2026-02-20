## Drago DataGrid

Drago DataGrid is a powerful and extendable tabular data component built on top of the Nette Framework.
It provides high-performance filtering, sorting, pagination, and row actions with flexible Latte templates for rendering Bootstrap 5 styled tables.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://github.com/drago-ex/datagrid/blob/main/license)
[![PHP version](https://badge.fury.io/ph/drago-ex%2Fdatagrid.svg)](https://badge.fury.io/ph/drago-ex%2Fdatagrid)
[![Coding Style](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml/badge.svg)](https://github.com/drago-ex/datagrid/actions/workflows/coding-style.yml)

## Requirements
- PHP >= 8.3
- Nette Framework 3.2+
- Dibi
- Latte
- Bootstrap 5
- Naja
- Composer

## ⚡ Features
- **Text & Date Filtering** – LIKE operator with SQL injection protection
- **Column Sorting** – Click headers to sort, toggle ASC/DESC
- **Smart Pagination** – LIMIT/OFFSET at DB level (5.8x faster for 1M rows)
- **Row Actions** – Edit, Delete, or custom actions with callbacks
- **Custom Formatting** – Format cell values with callbacks
- **Security Built-in** – SQL injection & XSS protection by default
- **Performance Optimized** – Only fetches data for current page
- **AJAX Integration** – Seamless Naja integration, no page refresh
- **Bootstrap 5** – Beautiful responsive styling included
- **Modular Architecture** – Easy to understand, test, and extend

## Installation
```bash
composer require drago-ex/datagrid
```
---
