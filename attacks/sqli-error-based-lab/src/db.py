import os
import sqlite3
from config import DB_PATH

SEED_PRODUCTS = [
    ("Laptop Pro 14", 1499.90, "Informatique"),
    ("Casque Audio", 129.99, "Audio"),
    ("Clavier Mécanique", 89.50, "Informatique"),
    ("Chaise Ergonomique", 249.00, "Bureau"),
    ("SSD 1TB", 99.99, "Informatique"),
]

def ensure_db_dir():
    folder = os.path.dirname(DB_PATH)
    if folder and not os.path.exists(folder):
        os.makedirs(folder, exist_ok=True)

def get_conn():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def init_db():
    ensure_db_dir()
    conn = get_conn()
    cur = conn.cursor()

    cur.execute("""
    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      price REAL NOT NULL,
      category TEXT NOT NULL
    )
    """)

    # état initial stable
    cur.execute("DELETE FROM products")
    cur.execute("DELETE FROM sqlite_sequence WHERE name='products'")
    cur.executemany(
        "INSERT INTO products (name, price, category) VALUES (?, ?, ?)",
        SEED_PRODUCTS
    )

    conn.commit()
    conn.close()