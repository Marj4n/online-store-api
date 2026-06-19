<?php

$mysqli = new mysqli(
    "localhost",
    "root",
    "",
    "online_store"
);

if ($mysqli->connect_error) {
    die("Database connection failed");
}