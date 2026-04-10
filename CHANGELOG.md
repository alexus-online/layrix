# Changelog

## 0.3.0 (2026-04-10)

### Fix
- Version bump for GitHub update detection after the Layrix rename.
- Plugin-Version für die GitHub-Update-Erkennung nach dem Layrix-Rename angehoben.
- Admin menu switched to the bundled Layrix SVG icon.
- Admin-Menü auf das gebündelte Layrix-SVG-Icon umgestellt.
- POT metadata cleaned and `.claude` worktrees excluded from translation references.
- POT-Metadaten bereinigt und `.claude`-Worktrees aus Übersetzungsreferenzen ausgeschlossen.

## 0.2.10 (2026-04-10)

### Fix
- Admin autosave stabilized after successful REST saves.
- Admin-Autosave nach erfolgreichen REST-Saves stabilisiert.
- Autosave support expanded for font presets, scale updates, repeated step fields, and recovery states.
- Autosave-Support für Schrift-Presets, Skalierungsänderungen, wiederholte Step-Felder und Recovery-Zustände erweitert.
- Frontend caches cleared after REST saves.
- Frontend-Caches nach REST-Saves geleert.
- Direct local-font import entry and tighter interface language loading.
- Direkten Einstieg für lokale Schriftimporte ergänzt und das Interface-Sprachladen robuster gemacht.

### Test
- Remote Playwright coverage expanded for autosave, import/export, language, font flows, and layout persistence.
- Remote-Playwright-Abdeckung für Autosave, Import/Export, Sprache, Schrift-Flows und Layout-Persistenz erweitert.
- Full admin UI suite verified: `83 passed`, `2 skipped`, `0 failed`.
- Komplette Admin-UI-Suite verifiziert: `83 bestanden`, `2 übersprungen`, `0 fehlgeschlagen`.

## 0.2.8 (2026-04-09)

### Fix
- Admin UI layout, typography pickers, sidebar/topbar behavior, and settings cards refined.
- Admin-UI-Layout, Typografie-Picker, Sidebar-/Topbar-Verhalten und Einstellungskarten verfeinert.
- Spacing preview ordering, typography samples, secondary class-sync styling, and spacing token truncation fixed.
- Reihenfolge der Abstände-Vorschau, Typografie-Beispiele, sekundäres Klassen-Sync-Styling und Token-Kürzung korrigiert.
- German tooltips, help texts, and admin copy expanded.
- Deutsche Tooltips, Hilfetexte und Admin-Texte erweitert.

### Test
- UI flows for spacing previews, typography samples, closed font pickers, masonry cards, and secondary class-sync styling added and verified.
- UI-Flows für Abstände-Vorschauen, Typografie-Beispiele, geschlossene Schrift-Picker, Masonry-Karten und sekundäres Klassen-Sync-Styling ergänzt und verifiziert.

## 0.2.7 (2026-04-08)

### Fix
- Duplicate changelog content removed from the Help panel.
- Doppelten Changelog-Inhalt aus dem Hilfe-Bereich entfernt.
- Missing German changelog action translation added.
- Fehlende deutsche Übersetzung für die Changelog-Aktion ergänzt.

### Test
- UI flow added and verified for changelog access without duplicate Help rendering.
- UI-Flow für Changelog-Zugriff ohne doppelte Help-Ausgabe ergänzt und verifiziert.

## 0.2.6 (2026-04-08)

### Fix
- Default dark mode restored for installs without a saved mode.
- Standard-Dunkelmodus für Installationen ohne gespeicherten Modus wiederhergestellt.
- Debug history clear action fixed.
- Aktion zum Leeren des Debug-Verlaufs korrigiert.
- Plugin textdomain loading hardened for language switching.
- Laden der Plugin-Textdomain für die Sprachumschaltung robuster gemacht.
- Global search delete flow stabilized after sync.
- Global-Search-Delete-Flow nach dem Sync stabilisiert.

### Test
- Full live UI suite verified: `33 passed`, `1 skipped`, `0 failed`.
- Komplette Live-UI-Suite verifiziert: `33 bestanden`, `1 übersprungen`, `0 fehlgeschlagen`.

## 0.2.5 (2026-04-08)

### Fix
- `--ecf-base-font-family` resolved to a real body font stack.
- `--ecf-base-font-family` auf einen echten Body-Font-Stack aufgelöst.
- Token-style names normalized in the UI.
- Token-artige Namen im UI normalisiert.
- Foreign-variable editing hardened with better validation and feedback.
- Bearbeitung fremder Variablen mit besserer Validierung und Rückmeldung gehärtet.

### Test
- Live UI flows for base font family resolution and token-name normalization added and verified.
- Live-UI-Flows für Basis-Schriftfamilie und Token-Normalisierung ergänzt und verifiziert.

## 0.2.4 (2026-04-07)

### Fix
- Remaining hardcoded admin JS messages removed.
- Verbleibende Hardcode-Meldungen im Admin-JS entfernt.
- Local-font placeholders, helper text, and preview labels moved into translations.
- Platzhalter, Hilfetexte und Preview-Labels in die Übersetzungen verschoben.

## 0.2.3 (2026-04-07)

### Fix
- Autosave extended to more dynamic UI actions.
- Autosave auf weitere dynamische UI-Aktionen erweitert.
- Direct token hint added below the base body text size field.
- Direkten Token-Hinweis unter dem Feld für die Basis-Schriftgröße ergänzt.

## 0.2.2 (2026-04-07)

### Fix
- Local font add flow fixed for the correct Typography section.
- Flow für lokale Schriften für den richtigen Typography-Bereich korrigiert.
- Narrow layout issues fixed in local font UI and Export / Import card.
- Schmale Layout-Probleme in lokaler Schrift-UI und Export-/Import-Karte behoben.

## 0.2.1 (2026-04-07)

### UX
- Favorites markers simplified to a calmer single-heart state.
- Favoriten-Markierung auf eine ruhigere Ein-Herz-Lösung vereinfacht.
- Favorites tab icon aligned with the rest of the flow.
- Favoriten-Tab-Icon an die restliche Logik angepasst.
- Default base body text size set to 16px.
- Standardwert der Basis-Schriftgröße auf 16px gesetzt.

## 0.2.0 (2026-04-06)

### Change
- Updater, Elementor status, changelog, and admin helpers split into modules.
- Updater, Elementor-Status, Changelog und Admin-Helfer in Module aufgeteilt.
- Main plugin file reduced without feature loss.
- Hauptdatei des Plugins ohne Funktionsverlust verkleinert.

## 0.1.20 (2026-04-06)

### UX
- General Settings split into System, Layout, and Behavior tabs.
- Allgemeine Einstellungen in System-, Layout- und Verhalten-Tabs aufgeteilt.
- Elementor Boxed Width synced as token and helper class.
- Elementor Boxed Width als Token und Helferklasse in den Sync aufgenommen.

## 0.1.19 (2026-04-06)

### Fix
- Class sync no longer overwrites existing Elementor global class values.
- Klassen-Sync überschreibt keine bestehenden globalen Elementor-Klassenwerte mehr.
- Existing class styles preserved while missing classes are still added.
- Bestehende Klassenstile bleiben erhalten, fehlende Klassen werden weiter ergänzt.

## 0.1.18 (2026-04-06)

### Fix
- Elementor sync flow hardened.
- Elementor-Sync robuster gemacht.
- Admin-post handling improved.
- admin-post-Handling verbessert.
- Spacing, typography, and radius previews stabilized.
- Abstands-, Typografie- und Radius-Vorschauen stabilisiert.

## 0.1.17 (2026-04-06)

### UX
- Class library flow refined.
- Klassenbibliothek klarer strukturiert.
- Guided BEM generator added.
- Geführten BEM-Generator ergänzt.
- Filters and compact helper states improved.
- Filter und kompakte Hilfszustände verbessert.

## 0.1.16 (2026-04-05)

### Fix
- Canonical plugin-folder fallback added for GitHub updates.
- Kanonischen Fallback für den Plugin-Ordner bei GitHub-Updates ergänzt.
- Installer kept on `Layrix`.
- Installer bei `Layrix` gehalten.

## 0.1.15 (2026-04-05)

### Fix
- GitHub update install path normalized.
- Installationspfad für GitHub-Updates normalisiert.
- Plugin folder kept as `Layrix` after updates.
- Plugin-Ordner nach Updates auf `Layrix` gehalten.

## 0.1.14 (2026-04-05)

### Feature
- Separate Elementor class overviews added for ECF and foreign classes.
- Eigene Klassenansichten für ECF- und fremde Elementor-Klassen ergänzt.
- Live counters added for variables and classes.
- Live-Zähler für Variablen und Klassen ergänzt.

### UX
- Utilities navigation label renamed to classes.
- Navigationspunkt Hilfsklassen in Klassen umbenannt.
- Generated example class block removed.
- Generierten Beispielblock entfernt.

## 0.1.13 (2026-04-04)

### Fix
- Plugin bootstrap guarded against duplicate class loading.
- Plugin-Bootstrap gegen doppelte Klassendeklaration abgesichert.
- Fatal activation errors after updates prevented.
- Fatale Aktivierungsfehler nach Updates verhindert.

## 0.1.12 (2026-04-04)

### Fix
- Active plugin state remembered before GitHub updates.
- Aktiven Plugin-Status vor GitHub-Updates gemerkt.
- Plugin reactivated automatically after updates.
- Plugin nach Updates automatisch reaktiviert.

## 0.1.11 (2026-04-04)

### Change
- Root Font Size moved out of the Typography panel.
- Root Font Size aus der Typografie herausgezogen.
- Root Font Size in a more general settings area placed.
- Root Font Size in einen allgemeineren Einstellungsbereich verschoben.

## 0.1.10 (2026-04-04)

### Change
- Output unified around `--ecf-*` variables and `.ecf-*` classes.
- Ausgabe auf `--ecf-*`-Variablen und `.ecf-*`-Klassen vereinheitlicht.
- Legacy `.cf-*` aliases kept for compatibility.
- Alte `.cf-*`-Aliase für Kompatibilität beibehalten.
- Typography, spacing, and radius tokens switched to rem output.
- Typografie-, Abstands- und Radius-Tokens auf rem-Ausgabe umgestellt.

## 0.1.9 (2026-04-04)

### UX
- Typography preview now shows real min/max font sizes.
- Typografie-Vorschau zeigt jetzt echte Min-/Max-Schriftgrößen.
- Raw clamp readout on the right replaced.
- Rohe Clamp-Anzeige auf der rechten Seite ersetzt.

## 0.1.8 (2026-04-04)

### Fix
- Elementor sync and cleanup handling improved.
- Elementor-Sync und Cleanup robuster gemacht.
- Clearer notices and automatic cache clearing added.
- Klarere Meldungen und automatische Cache-Leerung ergänzt.

### Feature
- Type tabs added for ECF and foreign Elementor variables.
- Typ-Tabs für ECF- und fremde Elementor-Variablen ergänzt.
- Large variable lists made easier to scan.
- Große Variablenlisten leichter scanbar gemacht.

## 0.1.7 (2026-04-04)

### Feature
- Selectable color formats added for HEX, HEXA, RGB, RGBA, HSL, and HSLA.
- Wählbare Farbformate für HEX, HEXA, RGB, RGBA, HSL und HSLA ergänzt.
- GitHub-based plugin update checks added with native WordPress support.
- GitHub-basierte Plugin-Update-Prüfung mit nativer WordPress-Unterstützung ergänzt.

### UX
- Color value field now updates the real color live.
- Farbwert-Feld aktualisiert die echte Farbe jetzt live.
- Helper badges replaced with direct hover tooltips.
- Helper-Badges durch direkte Hover-Tooltips ersetzt.
- Admin header alignment cleaned up.
- Ausrichtung des Admin-Headers bereinigt.
- Changelog view expanded with version and date history.
- Changelog-Ansicht um Versions- und Datumsverlauf erweitert.

## 0.1.6 (2026-04-03)

### Feature
- REST endpoints added for live ECF settings access and Elementor sync.
- REST-Endpunkte für Live-Zugriff auf ECF-Einstellungen und Elementor-Sync hinzugefügt.
- Live typography preview added inside the Typography tab.
- Live-Typografie-Vorschau im Typography-Tab hinzugefügt.
- Preset dropdown values added for type scale ratios.
- Vordefinierte Dropdown-Werte für Skalierungsverhältnisse hinzugefügt.
- Local font file management added via the WordPress media library.
- Verwaltung lokaler Schriftdateien über die WordPress-Mediathek hinzugefügt.
- Automatic `@font-face` output added for local font files.
- Automatische `@font-face`-Ausgabe für lokale Schriftdateien hinzugefügt.

### UX
- Typography tab layout reworked with a large preview panel.
- Typography-Tab mit großem Vorschau-Panel überarbeitet.
- Shadows tab aligned with the Typography UI.
- Schatten-Tab an die Typography-UI angeglichen.
- Shadow token column narrowed for tighter admin widths.
- Namensspalte der Schatten-Tokens für engere Admin-Breiten verschmälert.
- Shadow preview sizing rebalanced.
- Größenverhältnisse der Schattenvorschau angepasst.
- Visual typography utility showcases added.
- Visuelle Typography-Utility-Ansichten ergänzt.
- Utility and token table labels standardized.
- Tabellenlabels für Utilities und Tokens vereinheitlicht.
- Visible `Name` labels and placeholders standardized to `Class Name` / `Klassenname`.
- Sichtbare `Name`-Labels und Platzhalter auf `Class Name` / `Klassenname` vereinheitlicht.
- Backend branding renamed to `ECF Elementor v4 Core Framework`.
- Backend-Branding auf `ECF Elementor v4 Core Framework` umbenannt.
- Main shadow preview card reduced and matched to smaller tiles.
- Haupt-Schattenvorschau verkleinert und an kleinere Kacheln angeglichen.
- Indirect type scale factor controls replaced with direct min/max font size inputs.
- Indirekte Steuerung per Skalierungsfaktor durch direkte Min/Max-Schriftgrößen ersetzt.
- Separate min/max scale ratio controls added.
- Separate Min/Max-Skalierungsverhältnisse ergänzt.
- Default typography base values set to `16` and `18`.
- Standardwerte der Typografie-Basis auf `16` und `18` gesetzt.
- Device icons added and extended across typography previews and controls.
- Geräte-Icons in Typografie-Vorschauen und Steuerelementen ergänzt und erweitert.
- Local font usage restricted to same-site uploads files.
- Nutzung lokaler Schriften auf Dateien aus demselben Uploads-Ordner beschränkt.
- CSS-related sanitization hardened.
- Sanitization für CSS-relevante Einstellungen gehärtet.

### Security
- Stricter security checks added for framework actions and REST access.
- Strengere Sicherheitsprüfungen für Framework-Aktionen und REST-Zugriffe ergänzt.
- Sensitive plugin actions limited to users with `manage_options` and `activate_plugins`.
- Sensible Plugin-Aktionen auf Nutzer mit `manage_options` und `activate_plugins` beschränkt.
- Font MIME allowances scoped to authorized admin usage.
- Freigaben für Font-MIME-Typen auf autorisierte Admin-Nutzung begrenzt.
- Arbitrary external font URLs blocked from local font sources.
- Beliebige externe Font-URLs als lokale Schriftquellen blockiert.
