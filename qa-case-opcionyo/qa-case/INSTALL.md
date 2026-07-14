# INSTALL — Preparar la máquina desde cero

Guía para dejar cualquier máquina lista para correr este proyecto. Sólo
necesitás **PHP + Composer** para la suite principal, y **Node** si además querés
correr el E2E con Playwright.

Al final hay un **checklist de verificación** para confirmar que todo quedó bien.

---

## Windows

### 1. PHP + Composer (elegí UNA opción)

**Opción A — Laravel Herd (la más simple).** Trae PHP y Composer juntos.
1. Descargá e instalá desde <https://herd.laravel.com/windows>.
2. Cerrá y volvé a abrir la terminal (para refrescar el PATH).

**Opción B — winget (línea de comandos).** Abrí PowerShell y corré:
```powershell
winget install --id PHP.PHP.8.3 -e
winget install --id Composer.Composer -e
```
> Si Windows pide permisos de administrador (UAC), aprobalos. Cerrá y reabrí la
> terminal al terminar.

Asegurate de que estas extensiones de PHP estén activas (Herd ya las trae):
`mbstring`, `pdo_sqlite`, `sqlite3`, `openssl`, `curl`, `bcmath`.
Con winget, editá tu `php.ini` y quitá el `;` delante de esas líneas
`extension=...`.

### 2. Node (sólo para Playwright / E2E)
```powershell
winget install --id OpenJS.NodeJS.LTS -e
```

### 3. Git (si no lo tenés)
```powershell
winget install --id Git.Git -e
```

### 4. Stripe CLI (opcional — para reenviar webhooks reales en local)
```powershell
winget install --id Stripe.StripeCLI -e
```

---

## macOS

Con [Homebrew](https://brew.sh):

```bash
brew install php composer node git
brew install stripe/stripe-cli/stripe   # opcional (webhooks locales)
```

Las extensiones de PHP necesarias vienen incluidas en el `php` de Homebrew.

---

## Linux (Ubuntu/Debian)

```bash
sudo apt update
sudo apt install -y php8.3-cli php8.3-sqlite3 php8.3-mbstring php8.3-curl \
                    php8.3-bcmath unzip git

# Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# Node (para Playwright)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Stripe CLI (opcional): ver <https://docs.stripe.com/stripe-cli#install>.

---

## Cuentas externas (sólo si querés ir más allá de los mocks)

- **Stripe (modo test):** creá una cuenta gratis en
  <https://dashboard.stripe.com>, quedate en **Test mode** y copiá tu
  `Secret key` (`sk_test_...`) y `Publishable key` (`pk_test_...`).
  La suite corre **sin** esto (usa mocks); las claves sólo hacen falta para el
  test opt-in contra el sandbox y para reenviar webhooks reales.
- **AWS Chime:** **no** hace falta ninguna credencial — el SDK de Chime está
  mockeado en los tests.

---

## ✅ Verificación (copiá y pegá)

```bash
php --version        # >= 8.2
composer --version   # cualquier 2.x
node --version       # >= 18  (sólo si vas a correr el E2E)
git --version
php -m | grep -i -E "sqlite|mbstring|openssl|curl"   # deben aparecer
```

En Windows/PowerShell la última línea es:
```powershell
php -m | Select-String -Pattern "sqlite","mbstring","openssl","curl"
```

Si todo eso responde con versiones y las extensiones aparecen, la máquina está
lista. Seguí con **[RUN.md](RUN.md)**.

---

## Problemas comunes

| Síntoma | Causa / arreglo |
|--------|-----------------|
| `php` no se reconoce | Cerrá y reabrí la terminal; si usaste winget, confirmá que PHP quedó en el PATH. |
| `could not find driver` al migrar | Falta la extensión `pdo_sqlite`/`sqlite3`. Activala en `php.ini` (Herd ya la trae). |
| Composer falla por `allow_url_fopen` | Activá `allow_url_fopen=On` en `php.ini`. |
| Playwright no abre el navegador en CI/Linux | Corré `npx playwright install --with-deps chromium`. |
