# 🔐 IP Flood Protectie met PHP & .htaccess

Een eenvoudige maar doeltreffende oplossing voor het detecteren en blokkeren van IP-flood-aanvallen via PHP en `.htaccess`. Inclusief een visuele adminpagina om geblokkeerde IP-adressen te bekijken en handmatig te deblokkeren.

---

## 📦 Bestandsoverzicht

| Bestand             | Omschrijving |
|---------------------|--------------|
| `flood_protect.php` | Detecteert overmatige requests per IP en blokkeert deze automatisch via `.htaccess`. |
| `ip_log.json`       | Logbestand waarin alle IP-verzoeken en blokkadetijden worden opgeslagen. |
| `.htaccess`         | Wordt dynamisch aangepast om flooders te blokkeren (`Deny from IP`). |
| `flood_admin.php`   | Adminpagina om geblokkeerde IP's te bekijken en met één klik te deblokkeren. |

---

## 🚦 Hoe werkt het?

1. **Flood-detectie**  
   `flood_protect.php` telt per IP-adres het aantal verzoeken binnen een opgegeven tijdsinterval. Als het maximum wordt overschreden, wordt het IP:
   - toegevoegd aan `.htaccess` (`Deny from IP`)
   - opgeslagen in `ip_log.json` met een `blocked_until`-tijdstempel

2. **Automatisch deblokkeren**  
   Bij elk nieuw verzoek worden verlopen blokkades automatisch verwijderd uit `.htaccess`.

3. **Adminpagina**  
   `flood_admin.php` toont geblokkeerde IP’s en hoe lang de blokkade nog duurt. Je kunt IP’s met één klik deblokkeren.

4. **Gebruik**  
  Voeg bovenaan je PHP-pagina’s toe.
     ```php
   include 'flood_protect.php'; 

---

## ⚙️ Configuratie in `flood_protect.php`

```php
$maxRequests = 10;     // Max. aantal verzoeken
$timeWindow  = 60;     // Tijdvenster in seconden (bijv. 60s)
$blockDuration = 600;  // Blokkadetijd in seconden (bijv. 10 min)
