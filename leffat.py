import sqlite3
from tabulate import tabulate

conn = sqlite3.connect('./leffat2.db')
cur = conn.cursor()

print("LEFFATIETOKANTA")
print()
print("1. Näytä näyttelijät")
print("2. Näytä ohjaajat")
print("3. Näytä leffat")
print("4. Lisää näyttelijä")
print("5. Lisää ohjaaja")
print("6. Lisää leffa")
print("0. Lopeta")

valinta = input("Valitse toiminto: ")

if valinta == "1":
    limit = 5

    res = cur.execute('SELECT COUNT(*) FROM nayttelijat')
    maara = res.fetchone()[0]

    sivuja = (maara + limit - 1) // limit

    print("Sivuja yhteensä:", sivuja)
    sivu = int(input("Valitse sivu: "))

    offset = (sivu - 1) * limit

    res = cur.execute(
        'SELECT * FROM nayttelijat LIMIT ? OFFSET ?',
        (limit, offset)
    )

    data = res.fetchall()
    print(tabulate(data, headers=["ID", "Nimi", "Syntymävuosi", "Hotness"]))

elif valinta == "2":
    limit = 5

    res = cur.execute('SELECT COUNT(*) FROM ohjaajat')
    maara = res.fetchone()[0]

    sivuja = (maara + limit - 1) // limit

    print("Sivuja yhteensä:", sivuja)
    sivu = int(input("Valitse sivu: "))

    offset = (sivu - 1) * limit

    res = cur.execute(
        'SELECT * FROM ohjaajat LIMIT ? OFFSET ?',
        (limit, offset)
    )

    data = res.fetchall()
    print(tabulate(data, headers=["ID", "Nimi", "Syntymävuosi"]))

elif valinta == "3":
    limit = 5

    res = cur.execute('SELECT COUNT(*) FROM leffat')
    maara = res.fetchone()[0]

    sivuja = (maara + limit - 1) // limit

    print("Sivuja yhteensä:", sivuja)
    sivu = int(input("Valitse sivu: "))

    offset = (sivu - 1) * limit

    res = cur.execute(
        '''
        SELECT leffat.nimi,
               leffat.valmistumisvuosi,
               ohjaajat.nimi,
               nayttelijat.nimi
        FROM leffat
        JOIN ohjaajat
        JOIN nayttelijat
        WHERE leffat.ohjaaja_id = ohjaajat.id
        AND leffat.nayttelija_id = nayttelijat.id
        LIMIT ? OFFSET ?
        ''',
        (limit, offset)
    )

    data = res.fetchall()
    print(tabulate(
        data,
        headers=["Leffa", "Valmistumisvuosi", "Ohjaaja", "Näyttelijä"]
    ))

elif valinta == "4":
    nimi = input("Anna näyttelijän nimi: ")
    vuosi = int(input("Anna näyttelijän syntymävuosi: "))
    hotness = int(input("Anna näyttelijän hotness: "))

    cur.execute(
        'INSERT INTO nayttelijat (nimi, syntymavuosi, hotness) VALUES (?, ?, ?)',
        (nimi, vuosi, hotness)
    )

    conn.commit()

    print("Näyttelijä added.")    
    
elif valinta == "5":
    nimi = input("Anna ohjaajan nimi: ")
    vuosi = int(input("Anna ohjaajan syntymävuosi: "))

    cur.execute(
        'INSERT INTO ohjaajat (nimi, syntymavuosi) VALUES (?, ?)',
        (nimi, vuosi)
    )

    conn.commit()

    print("Ohjaaja added.")    
    
elif valinta == "6":
    nimi = input("Anna leffan nimi: ")
    vuosi = int(input("Anna leffan valmistumisvuosi: "))

    # Näytä ohjaajat
    res = cur.execute('SELECT id, nimi FROM ohjaajat')
    ohjaajat = res.fetchall()

    print("\nOhjaajat:")
    print(tabulate(ohjaajat, headers=["ID", "Nimi"]))

    ohjaaja_id = int(input("Valitse ohjaaja ID: "))

    # Näytä näyttelijät
    res = cur.execute('SELECT id, nimi FROM nayttelijat')
    nayttelijat = res.fetchall()

    print("\nNäyttelijät:")
    print(tabulate(nayttelijat, headers=["ID", "Nimi"]))

    nayttelija_id = int(input("Valitse näyttelijä ID: "))

    cur.execute(
        '''INSERT INTO leffat
        (nimi, valmistumisvuosi, ohjaaja_id, nayttelija_id)
        VALUES (?, ?, ?, ?)''',
        (nimi, vuosi, ohjaaja_id, nayttelija_id)
    )

    conn.commit()

    print("Leffa added.")    
conn.close()