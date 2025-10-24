# QuizSync

QuizSync je jednoduchá LAMP aplikace postavená na frameworku Nette, která umožňuje pořadatelům spouštět živé kvízy s mobilními ovladači hráčů. Na stolním zařízení běží hostitelský panel pro správu hry, hráči se připojují přes QR kód a odpovídají na otázky se třemi možnostmi. Otázky jsou získávány online z české Wikipedie, aby bylo možné snadno průběžně doplňovat obsah bez vlastní správy databáze znalostí.

## Požadavky

- PHP 8.1+
- Composer
- MySQL 8.0+ (nebo MariaDB 10.5+)
- Webserver (Apache nebo Nginx) s podporou mod_php / PHP-FPM

## Instalace

1. Naklonuj repozitář a nainstaluj závislosti:

   ```bash
   composer install
   ```

2. Vytvoř databázi a uživatele, poté spusť SQL migraci z `db/migrations/001_create_tables.sql`.

3. Zkopíruj konfigurační soubor a doplň přístupové údaje k databázi:

   ```bash
   cp app/config/local.neon.example app/config/local.neon
   ```

4. V kořenovém adresáři projektu nastav práva pro adresář `temp/` tak, aby do něj mohl zapisovat uživatel běžícího webserveru.

5. Nakonfiguruj webserver tak, aby obsluhoval adresář `www/` jako document root. Pro lokální vývoj lze použít PHP built-in server:

   ```bash
   php -S 0.0.0.0:8000 -t www
   ```

6. Otevři hostitelský panel v prohlížeči na `http://localhost:8000/`.

## Průběh hry

1. V hostitelském panelu zvol počet otázek a čas pro odpověď, poté vytvoř novou hru.
2. Na hostitelské obrazovce registruj hráče zadáním jména. Ke každému hráči se zobrazí QR kód s adresou mobilního ovladače.
3. Po stisku tlačítka **Zahájit hru** se všem účastníkům zobrazí otázka se třemi možnostmi a začne běžet časový limit.
4. Po vypršení času lze odpověď odhalit, bodování se propíše do živého žebříčku.
5. Po odehrání všech otázek se hra automaticky uzavře a zůstane vidět pořadí hráčů.
6. Hráč může kdykoli z mobilního ovladače hru opustit (odhlásit se), čímž se zneplatní jeho token.

> QR kódy jsou generovány přes veřejné API [goqr.me](https://goqr.me/api/); pokud chceš provozovat řešení zcela offline, stačí v `www/js/host.js` nahradit URL generátoru za vlastní (např. lokální knihovna).

## Hlasová syntéza – rešerše

Pro budoucí rozšíření o čtení otázek nahlas lze využít tyto služby:

| Služba | Jazyky | Odhad ceny* | Poznámky |
| ------ | ------ | ----------- | -------- |
| [Amazon Polly](https://aws.amazon.com/polly/) | Čeština (Lucie, Antonín) | 4 USD za 1 milion znaků | Snadná integrace přes REST API, podporuje i neurální hlasy. Vyžaduje AWS účet a konfiguraci IAM. |
| [Microsoft Azure Speech](https://azure.microsoft.com/products/ai-services/text-to-speech/) | Čeština (Katka, Antonin) | 16 USD za 1 milion znaků (neurální) | Výborná kvalita, možnost ukládání do cache přes Speech SDK. Nutný Azure účet a vytvoření Speech resource. |
| [Google Cloud Text-to-Speech](https://cloud.google.com/text-to-speech) | Čeština (Standard i WaveNet) | 16 USD za 1 milion znaků (WaveNet) | Spolehlivé API, možnost přednačítání do lokálního úložiště. Vyžaduje služební účet a správu klíčů. |

\*Ceny k dubnu 2024, bez započtení bezplatných kreditů pro nové uživatele. Pro produkční nasazení doporučuji implementovat jednoduchou cache na úrovni aplikace (např. ukládání MP3 souborů do lokálního úložiště), aby se snížily náklady při opakovaném spouštění stejných otázek.

## Kontejnerizace – doporučený postup

Aplikace zatím neobsahuje Docker konfigurační soubory. Pro nasazení do domácího prostředí (např. společně s Jellyfin) doporučuji následující kroky:

1. **Dockerfile pro PHP-Apache** – využij oficiální obraz `php:8.2-apache`, doinstaluj rozšíření `pdo_mysql` a nakopíruj aplikaci do `/var/www/html`. Do kontejneru zaveď konfigurační soubor `app/config/local.neon` jako svazek, aby bylo možné oddělit tajné údaje.
2. **Docker Compose** – vytvoř `docker-compose.yml`, který zprovozní PHP-Apache kontejner a navázanou službu `mysql:8.0`. MySQL kontejneru namapuj persistentní svazek a inicializační skript s migrací.
3. **Reverse proxy** – pokud již používáš Traefik nebo Nginx Proxy Manager, přidej nový virtuální host směrující na port PHP-Apache kontejneru. HTTPS certifikát může obstarávat stávající proxy.
4. **Zdravotní kontrola** – přidej jednoduchý endpoint (např. `HEAD /`) pro healthcheck kontejneru, aby bylo možné snadno monitorovat dostupnost.

Po přípravě Dockerfile a Compose souboru bude možné aplikaci jednoduše spustit příkazem `docker compose up -d` a integrovat ji mezi stávající domácí služby.

## Testy

Projekt zatím neobsahuje automatické testy. Kritické části (správa hry, API) je možné testovat ručně pomocí HTTP klienta nebo Cypressu pro e2e scénáře.
