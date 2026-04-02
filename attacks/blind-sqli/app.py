from flask import Flask, request, render_template
import sqlite3

app = Flask(__name__)

def get_user(username):
    conn = sqlite3.connect("users.db")
    cursor = conn.cursor()

    # requête vulnérable SQL injection
    query = f"SELECT * FROM users WHERE username = '{username}'"
    result = cursor.execute(query).fetchone()

    conn.close()
    return result


@app.route("/", methods=["GET","POST"])
def index():

    message = ""

    if request.method == "POST":
        username = request.form["username"]

        user = get_user(username)

        if user:
            message = "User exists"
        else:
            message = "User not found"

    return render_template("index.html", message=message)


if __name__ == "__main__":

    conn = sqlite3.connect("users.db")
    cursor = conn.cursor()

    cursor.execute("""
    CREATE TABLE IF NOT EXISTS users(
        id INTEGER PRIMARY KEY,
        username TEXT,
        password TEXT
    )
    """)

    users = [
        ("admin","secret123"),
        ("john","password1"),
        ("alice","alice123"),
        ("bob","bobpass"),
        ("charlie","charlie123"),
        ("david","davidpass"),
        ("emma","emma123"),
        ("frank","frankpass"),
        ("grace","grace123"),
        ("henry","henrypass"),
        ("isabella","isa123"),
        ("jack","jackpass"),
        ("karen","karen123"),
        ("leo","leopass"),
        ("maria","maria123"),
        ("nathan","nathanpass"),
        ("olivia","olivia123"),
        ("paul","paulpass"),
        ("quinn","quinn123"),
        ("robert","robertpass")
    ]

    for user in users:
        cursor.execute("INSERT OR IGNORE INTO users(username,password) VALUES(?,?)", user)

    conn.commit()
    conn.close()

    app.run(host="0.0.0.0", port=5000)
