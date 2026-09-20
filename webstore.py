import mysql.connector

conn = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="webstore"
)

cur = conn.cursor()

print("VERKKOKAUPPA")

print("1. Näytä tuotteet")
print("2. Näytä asiakkaat")
print("3. Näytä tilaukset")
print("4. Lisää tuote")
print("5. Lisää asiakas")
print("6. Lisää tilaus")
print("7. Poista tuote")
print("8. Poista asiakas")
print("9. Poista tilaus")
print("10. Näytä asiakkaan tilaukset")
print("11. Näytä tuotteen tilaukset")
print("0. Lopeta")

valinta = input("Valitse toiminto: ")

if valinta == "1":
    limit = 5

    cur.execute("SELECT COUNT(*) FROM products")
    maara = cur.fetchone()[0]

    sivuja = (maara + limit - 1) // limit
    print("Sivuja yhteensä:", sivuja)

    sivu = int(input("Valitse sivu: "))
    offset = (sivu - 1) * limit

    cur.execute(
        "SELECT * FROM products LIMIT %s OFFSET %s",
        (limit, offset)
    )

    data = cur.fetchall()

    for rivi in data:
        print(rivi)
        
elif valinta == "2":
    limit = 5

    cur.execute("SELECT COUNT(*) FROM customers")
    maara = cur.fetchone()[0]

    sivuja = (maara + limit - 1) // limit
    print("Sivuja yhteensä:", sivuja)

    sivu = int(input("Valitse sivu: "))
    offset = (sivu - 1) * limit

    cur.execute(
        "SELECT * FROM customers LIMIT %s OFFSET %s",
        (limit, offset)
    )

    data = cur.fetchall()

    for rivi in data:
        print(rivi)
        
elif valinta == "3":
    limit = 5

    cur.execute("SELECT COUNT(*) FROM orders")
    maara = cur.fetchone()[0]

    sivuja = (maara + limit - 1) // limit
    print("Sivuja yhteensä:", sivuja)

    sivu = int(input("Valitse sivu: "))
    offset = (sivu - 1) * limit

    cur.execute("""
        SELECT
            orders.id,
            customers.first_name,
            customers.last_name,
            products.name,
            products.price,
            orders.order_date
        FROM orders
        JOIN customers ON orders.customer_id = customers.id
        JOIN products ON orders.product_id = products.id
        LIMIT %s OFFSET %s
    """, (limit, offset))

    data = cur.fetchall()

    for rivi in data:
        print(rivi)
        
elif valinta == "4":
    nimi = input("Anna tuotteen nimi: ")
    hinta = float(input("Anna tuotteen hinta: "))

    cur.execute(
        "INSERT INTO products (name, price) VALUES (%s, %s)",
        (nimi, hinta)
    )

    conn.commit()
    print("Tuote added.")    
    
elif valinta == "5":
    etunimi = input("Anna etunimi: ")
    sukunimi = input("Anna sukunimi: ")
    osoite = input("Anna osoite: ")

    cur.execute(
        "INSERT INTO customers (first_name, last_name, address) VALUES (%s, %s, %s)",
        (etunimi, sukunimi, osoite)
    )

    conn.commit()
    print("Asiakas added.")
    
elif valinta == "6":
    print("\nTuotteet:")
    cur.execute("SELECT id, name FROM products")
    tuotteet = cur.fetchall()

    for tuote in tuotteet:
        print(tuote)

    product_id = int(input("Valitse tuotteen ID: "))

    print("\nAsiakkaat:")
    cur.execute("SELECT id, first_name, last_name FROM customers")
    asiakkaat = cur.fetchall()

    for asiakas in asiakkaat:
        print(asiakas)

    customer_id = int(input("Valitse asiakkaan ID: "))
    paivamaara = input("Anna tilauksen päivämäärä (YYYY-MM-DD): ")

    cur.execute(
        """INSERT INTO orders (product_id, customer_id, order_date)
        VALUES (%s, %s, %s)""",
        (product_id, customer_id, paivamaara)
    )

    conn.commit()
    print("Tilaus added.")
    
elif valinta == "7":
    cur.execute("SELECT id, name, price FROM products")
    tuotteet = cur.fetchall()

    print("\nTuotteet:")
    for tuote in tuotteet:
        print(tuote)

    product_id = int(input("Anna poistettavan tuotteen ID: "))
    
    cur.execute(
    "DELETE FROM orders WHERE product_id = %s",
    (product_id,)
    )
    
    cur.execute(
        "DELETE FROM products WHERE id = %s",
        (product_id,)
    )

    conn.commit()
    print("Tuote deleted.")
    
elif valinta == "8":
    cur.execute("SELECT id, first_name, last_name FROM customers")
    asiakkaat = cur.fetchall()

    print("\nAsiakkaat:")
    for asiakas in asiakkaat:
        print(asiakas)

    customer_id = int(input("Anna poistettavan asiakkaan ID: "))

    cur.execute(
    "DELETE FROM orders WHERE customer_id = %s",
    (customer_id,)
    )
    
    cur.execute(
        "DELETE FROM customers WHERE id = %s",
        (customer_id,)
    )

    conn.commit()
    print("Asiakas deleted.")                        
    
    
elif valinta == "9":
    cur.execute("""
        SELECT
            orders.id,
            customers.first_name,
            customers.last_name,
            products.name,
            orders.order_date
        FROM orders
        JOIN customers ON orders.customer_id = customers.id
        JOIN products ON orders.product_id = products.id
    """)

    tilaukset = cur.fetchall()

    print("\nTilaukset:")
    for tilaus in tilaukset:
        print(tilaus)

    order_id = int(input("Anna poistettavan tilauksen ID: "))

    cur.execute(
        "DELETE FROM orders WHERE id = %s",
        (order_id,)
    )

    conn.commit()
    print("Tilaus deleted.")    
    
elif valinta == "10":
    cur.execute("SELECT id, first_name, last_name FROM customers")
    asiakkaat = cur.fetchall()

    print("\nAsiakkaat:")
    for asiakas in asiakkaat:
        print(asiakas)

    customer_id = int(input("Valitse asiakkaan ID: "))

    limit = 5

    cur.execute(
        "SELECT COUNT(*) FROM orders WHERE customer_id = %s",
        (customer_id,)
    )
    maara = cur.fetchone()[0]

    sivuja = (maara + limit - 1) // limit
    print("Sivuja yhteensä:", sivuja)

    sivu = int(input("Valitse sivu: "))
    offset = (sivu - 1) * limit

    cur.execute("""
        SELECT
            orders.id,
            customers.first_name,
            customers.last_name,
            products.name,
            products.price,
            orders.order_date
        FROM orders
        JOIN customers ON orders.customer_id = customers.id
        JOIN products ON orders.product_id = products.id
        WHERE orders.customer_id = %s
        LIMIT %s OFFSET %s
    """, (customer_id, limit, offset))

    data = cur.fetchall()

    for rivi in data:
        print(rivi)    
        
elif valinta == "11":
    cur.execute("SELECT id, name FROM products")
    tuotteet = cur.fetchall()

    print("\nTuotteet:")
    for tuote in tuotteet:
        print(tuote)

    product_id = int(input("Valitse tuotteen ID: "))

    limit = 5

    cur.execute(
        "SELECT COUNT(*) FROM orders WHERE product_id = %s",
        (product_id,)
    )
    maara = cur.fetchone()[0]

    sivuja = (maara + limit - 1) // limit
    print("Sivuja yhteensä:", sivuja)

    sivu = int(input("Valitse sivu: "))
    offset = (sivu - 1) * limit

    cur.execute("""
        SELECT
            orders.id,
            customers.first_name,
            customers.last_name,
            products.name,
            products.price,
            orders.order_date
        FROM orders
        JOIN customers ON orders.customer_id = customers.id
        JOIN products ON orders.product_id = products.id
        WHERE orders.product_id = %s
        LIMIT %s OFFSET %s
    """, (product_id, limit, offset))

    data = cur.fetchall()

    for rivi in data:
        print(rivi)   
        
elif valinta == "0":
    print("Ohjelma lopetettu.")    
    
conn.close()             