# Dedicated Servers Module — MyAdmin Plugin

## Overview
Plugin module (`detain/myadmin-servers-module`) for dedicated server lifecycle in MyAdmin. Namespace: `Detain\MyAdminServers\` → `src/`. Module key: `servers`, type: `module`.

## Commands
```bash
composer install          # install deps
vendor/bin/phpunit        # run tests (phpunit.xml.dist)
```

## Architecture

**Entry**: `src/Plugin.php` — static `$settings`, `getHooks()`, and lifecycle handlers
**Hooks**: `servers.activate` · `servers.deactivate` · `servers.load_processing` · `servers.settings`
**DB**: table `servers` · prefix `server_` · title field `server_hostname`
**Settings**: `Plugin::$settings` — `SERVICE_ID_OFFSET=4000`, `TABLE='servers'`, `PREFIX='server'`, `SUSPEND_DAYS=14`, `DELETE_PENDING_DAYS=45`
**Bin scripts**: `bin/check_on_legacy_invoices.php` · `bin/get_server_network_info.php` · `bin/update_ipmi.php` · `bin/update_server_cpanel_licenses.php` · `bin/update_switchports.php`
**Tests**: `tests/PluginTest.php` · bootstrap `tests/bootstrap.php` · config `phpunit.xml.dist`

## Event Hook Pattern

```php
public static function getHooks() {
    return [
        self::$module.'.activate' => [__CLASS__, 'getActivate'],
        self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
        self::$module.'.settings' => [__CLASS__, 'getSettings']
    ];
}

public static function getActivate(GenericEvent $event) {
    $serviceClass = $event->getSubject();
    myadmin_log(self::$module, 'info', 'Dedicated Server Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
    $event->stopPropagation();
}
```

## Service Lifecycle — `loadProcessing()`

```php
$service->setEnable(function ($service) {
    $settings = get_module_settings(self::$module);
    $db = get_module_db(self::$module);
    $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active-billing' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
    $GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $id, $custid);
})->setReactivate(...)->setDisable(...)->setTerminate(...)->register();
```

## DB Pattern
- `$db = get_module_db(self::$module)` or `$db = clone $GLOBALS['tf']->db`
- `$db->query("SELECT ...", __LINE__, __FILE__)` · `$db->next_record(MYSQL_ASSOC)` · `$db->Record`
- `make_insert_query($table, $assoc)` for inserts · `$db->getLastInsertId($table, 'col_id')`
- Never use PDO

## Bin Script Bootstrap
```php
include_once __DIR__.'/../../../../include/functions.inc.php';
$GLOBALS['tf']->session->create(160307, 'services', false, 0, false, substr(basename($_SERVER['argv'][0], '.php'), 0, 32));
$db = clone $GLOBALS['tf']->db;
```

## Conventions
- Commit messages: lowercase, descriptive
- Log: `myadmin_log(self::$module, 'info'|'warning', $msg, __LINE__, __FILE__, self::$module, $id)`
- Email: `(new \MyAdmin\Mail())->adminMail($subject, $email, false, 'admin/template.tpl')`
- Smarty: `$smarty = new \TFSmarty(); $smarty->assign('key', $val); $smarty->fetch('email/admin/template.tpl')`
- Settings dropdown: `$settings->add_dropdown_setting(self::$module, _('General'), 'outofstock_servers', ...)`
- Run `caliber refresh && git add CLAUDE.md` before committing

<!-- caliber:managed:pre-commit -->
## Before Committing

**IMPORTANT:** Before every git commit, you MUST ensure Caliber syncs agent configs with the latest code changes.

First, check if the pre-commit hook is already installed:
```bash
grep -q "caliber" .git/hooks/pre-commit 2>/dev/null && echo "hook-active" || echo "no-hook"
```

- If **hook-active**: the hook handles sync automatically — just commit normally. Tell the user: "Caliber will sync your agent configs automatically via the pre-commit hook."
- If **no-hook**: run Caliber manually before committing:
  1. Tell the user: "Caliber: Syncing agent configs with your latest changes..."
  2. Run: `caliber refresh && git add CLAUDE.md .claude/ .cursor/ .cursorrules .github/copilot-instructions.md .github/instructions/ AGENTS.md CALIBER_LEARNINGS.md .agents/ .opencode/ 2>/dev/null`
  3. After it completes, briefly tell the user what Caliber updated. Then proceed with the commit.

If `caliber` is not found, tell the user: "This project uses Caliber for agent config sync. Run /setup-caliber to get set up."
<!-- /caliber:managed:pre-commit -->

<!-- caliber:managed:learnings -->
## Session Learnings

Read `CALIBER_LEARNINGS.md` for patterns and anti-patterns learned from previous sessions.
These are auto-extracted from real tool usage — treat them as project-specific rules.
<!-- /caliber:managed:learnings -->
