import mysql.connector
from mysql.connector import Error


# KONFIGURASI KONEKSI DATABASE
KONFIG_DB = {
    "host":     "localhost",
    "user":     "root",
    "password": "",          # isi password MySQL kamu (kalau tidak ada, biarkan kosong)
    "database": "db_perpustakaan"
}

def koneksi():
    """Membuka koneksi ke database."""
    try:
        conn = mysql.connector.connect(**KONFIG_DB)
        return conn
    except Error as e:
        print(f"\n[ERROR] Tidak bisa konek ke database: {e}")
        return None


# MODUL PETUGAS
def tampil_semua_petugas():
    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    cursor.execute("SELECT id_petugas, nama, username, no_telepon FROM petugas ORDER BY id_petugas")
    data = cursor.fetchall()
    conn.close()

    print("\n" + "="*55)
    print(f"  {'ID':<5} {'NAMA':<22} {'USERNAME':<12} {'TELEPON'}")
    print("="*55)
    if not data:
        print("  (Belum ada data petugas)")
    for row in data:
        print(f"  {row[0]:<5} {row[1]:<22} {row[2]:<12} {row[3] or '-'}")
    print("="*55)


def tambah_petugas():
    print("\n--- Tambah Petugas Baru ---")
    nama      = input("Nama lengkap  : ").strip()
    username  = input("Username      : ").strip()
    password  = input("Password      : ").strip()
    telepon   = input("No. telepon   : ").strip()

    if not nama or not username or not password:
        print("[!] Nama, username, dan password wajib diisi.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    try:
        cursor.execute(
            "INSERT INTO petugas (nama, username, password, no_telepon) VALUES (%s, %s, %s, %s)",
            (nama, username, password, telepon or None)
        )
        conn.commit()
        print(f"[OK] Petugas '{nama}' berhasil ditambahkan.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def edit_petugas():
    tampil_semua_petugas()
    try:
        id_p = int(input("\nMasukkan ID petugas yang ingin diedit: "))
    except ValueError:
        print("[!] ID harus berupa angka.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    cursor.execute("SELECT nama, username, no_telepon FROM petugas WHERE id_petugas = %s", (id_p,))
    row = cursor.fetchone()
    if not row:
        print("[!] ID petugas tidak ditemukan.")
        conn.close()
        return

    print(f"\nData saat ini  ->  Nama: {row[0]}  |  Username: {row[1]}  |  Telepon: {row[2]}")
    print("(Tekan Enter untuk tidak mengubah)")

    nama_baru    = input(f"Nama baru      : ").strip() or row[0]
    username_baru= input(f"Username baru  : ").strip() or row[1]
    telepon_baru = input(f"Telepon baru   : ").strip() or row[2]

    try:
        cursor.execute(
            "UPDATE petugas SET nama=%s, username=%s, no_telepon=%s WHERE id_petugas=%s",
            (nama_baru, username_baru, telepon_baru, id_p)
        )
        conn.commit()
        print("[OK] Data petugas berhasil diperbarui.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def hapus_petugas():
    tampil_semua_petugas()
    try:
        id_p = int(input("\nMasukkan ID petugas yang ingin dihapus: "))
    except ValueError:
        print("[!] ID harus berupa angka.")
        return

    konfirmasi = input(f"Yakin hapus petugas ID {id_p}? (y/n): ").strip().lower()
    if konfirmasi != 'y':
        print("Dibatalkan.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    try:
        cursor.execute("DELETE FROM petugas WHERE id_petugas = %s", (id_p,))
        conn.commit()
        if cursor.rowcount:
            print("[OK] Petugas berhasil dihapus.")
        else:
            print("[!] ID tidak ditemukan.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def menu_petugas():
    while True:
        print("\n╔══════════════════════════╗")
        print("║    MANAJEMEN PETUGAS     ║")
        print("╠══════════════════════════╣")
        print("║  1. Lihat semua petugas  ║")
        print("║  2. Tambah petugas       ║")
        print("║  3. Edit petugas         ║")
        print("║  4. Hapus petugas        ║")
        print("║  0. Kembali              ║")
        print("╚══════════════════════════╝")
        pilihan = input("Pilih menu: ").strip()

        if   pilihan == "1": tampil_semua_petugas()
        elif pilihan == "2": tambah_petugas()
        elif pilihan == "3": edit_petugas()
        elif pilihan == "4": hapus_petugas()
        elif pilihan == "0": break
        else: print("[!] Pilihan tidak valid.")


# MODUL ANGGOTA
def tampil_semua_anggota():
    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    cursor.execute("""
        SELECT a.id_anggota, a.nama, a.alamat, a.no_telepon,
               a.tanggal_daftar, p.nama
        FROM anggota a
        LEFT JOIN petugas p ON a.id_petugas = p.id_petugas
        ORDER BY a.id_anggota
    """)
    data = cursor.fetchall()
    conn.close()

    print("\n" + "="*78)
    print(f"  {'ID':<5} {'NAMA':<20} {'TELEPON':<14} {'TGL DAFTAR':<13} {'DIDAFTARKAN OLEH'}")
    print("="*78)
    if not data:
        print("  (Belum ada data anggota)")
    for row in data:
        print(f"  {row[0]:<5} {row[1]:<20} {row[3] or '-':<14} {str(row[4]):<13} {row[5] or '-'}")
    print("="*78)


def tambah_anggota():
    print("\n--- Tambah Anggota Baru ---")

    # Tampilkan daftar petugas untuk pilihan
    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    cursor.execute("SELECT id_petugas, nama FROM petugas ORDER BY id_petugas")
    petugas_list = cursor.fetchall()
    conn.close()

    if not petugas_list:
        print("[!] Belum ada data petugas. Tambah petugas terlebih dahulu.")
        return

    print("\nDaftar petugas:")
    for p in petugas_list:
        print(f"  [{p[0]}] {p[1]}")

    nama     = input("\nNama anggota  : ").strip()
    alamat   = input("Alamat        : ").strip()
    telepon  = input("No. telepon   : ").strip()
    tgl      = input("Tgl daftar (YYYY-MM-DD) [Enter = hari ini 2026-06-06]: ").strip() or "2026-06-06"

    try:
        id_p = int(input("ID petugas yang mendaftarkan: ").strip())
    except ValueError:
        print("[!] ID petugas harus angka.")
        return

    if not nama:
        print("[!] Nama wajib diisi.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    try:
        cursor.execute(
            "INSERT INTO anggota (nama, alamat, no_telepon, tanggal_daftar, id_petugas) VALUES (%s, %s, %s, %s, %s)",
            (nama, alamat or None, telepon or None, tgl, id_p)
        )
        conn.commit()
        print(f"[OK] Anggota '{nama}' berhasil ditambahkan.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def edit_anggota():
    tampil_semua_anggota()
    try:
        id_a = int(input("\nMasukkan ID anggota yang ingin diedit: "))
    except ValueError:
        print("[!] ID harus berupa angka.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    cursor.execute("SELECT nama, alamat, no_telepon FROM anggota WHERE id_anggota = %s", (id_a,))
    row = cursor.fetchone()
    if not row:
        print("[!] ID anggota tidak ditemukan.")
        conn.close()
        return

    print(f"\nData saat ini  ->  Nama: {row[0]}  |  Alamat: {row[1]}  |  Telepon: {row[2]}")
    print("(Tekan Enter untuk tidak mengubah)")

    nama_baru   = input("Nama baru     : ").strip() or row[0]
    alamat_baru = input("Alamat baru   : ").strip() or row[1]
    telepon_baru= input("Telepon baru  : ").strip() or row[2]

    try:
        cursor.execute(
            "UPDATE anggota SET nama=%s, alamat=%s, no_telepon=%s WHERE id_anggota=%s",
            (nama_baru, alamat_baru, telepon_baru, id_a)
        )
        conn.commit()
        print("[OK] Data anggota berhasil diperbarui.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def hapus_anggota():
    tampil_semua_anggota()
    try:
        id_a = int(input("\nMasukkan ID anggota yang ingin dihapus: "))
    except ValueError:
        print("[!] ID harus berupa angka.")
        return

    konfirmasi = input(f"Yakin hapus anggota ID {id_a}? (y/n): ").strip().lower()
    if konfirmasi != 'y':
        print("Dibatalkan.")
        return

    conn = koneksi()
    if not conn:
        return
    cursor = conn.cursor()
    try:
        cursor.execute("DELETE FROM anggota WHERE id_anggota = %s", (id_a,))
        conn.commit()
        if cursor.rowcount:
            print("[OK] Anggota berhasil dihapus.")
        else:
            print("[!] ID tidak ditemukan.")
    except Error as e:
        print(f"[ERROR] {e}")
    finally:
        conn.close()


def menu_anggota():
    while True:
        print("\n╔══════════════════════════╗")
        print("║    MANAJEMEN ANGGOTA     ║")
        print("╠══════════════════════════╣")
        print("║  1. Lihat semua anggota  ║")
        print("║  2. Tambah anggota       ║")
        print("║  3. Edit anggota         ║")
        print("║  4. Hapus anggota        ║")
        print("║  0. Kembali              ║")
        print("╚══════════════════════════╝")
        pilihan = input("Pilih menu: ").strip()

        if   pilihan == "1": tampil_semua_anggota()
        elif pilihan == "2": tambah_anggota()
        elif pilihan == "3": edit_anggota()
        elif pilihan == "4": hapus_anggota()
        elif pilihan == "0": break
        else: print("[!] Pilihan tidak valid.")

# MENU UTAMA
def main():
    print("\n" + "="*35)
    print("   SISTEM INFORMASI PERPUSTAKAAN")
    print("="*35)

    while True:
        print("\n╔══════════════════════════════╗")
        print("║         MENU UTAMA           ║")
        print("╠══════════════════════════════╣")
        print("║  1. Manajemen Petugas        ║")
        print("║  2. Manajemen Anggota        ║")
        print("║  0. Keluar                   ║")
        print("╚══════════════════════════════╝")
        pilihan = input("Pilih menu: ").strip()

        if   pilihan == "1": menu_petugas()
        elif pilihan == "2": menu_anggota()
        elif pilihan == "0":
            print("\nTerima kasih. Sampai jumpa!\n")
            break
        else:
            print("[!] Pilihan tidak valid.")


if __name__ == "__main__":
    main()
