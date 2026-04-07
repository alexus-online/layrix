# Changelog

## 0.2.1 (2026-04-07)

### UX
- Reworked favorites markers to a calmer single-heart state with clearer status feedback.
- Favoriten-Markierung auf eine ruhigere Ein-Herz-Lösung mit klarerem Status umgestellt.
- Made the Favorites tab icon consistent with the rest of the favorites flow.
- Favoriten-Tab-Icon wieder konsistent zur restlichen Favoritenlogik gemacht.
- Switched the default base body text size to 16px for easier understanding.
- Standardwert der Basis-Schriftgröße für Fließtext auf 16px umgestellt.

## 0.2.0 (2026-04-06)

### Change
- Split updater, Elementor status, changelog, and general admin helpers into separate modules.
- Updater, Elementor-Status, Changelog und allgemeine Admin-Helfer in eigene Module aufgeteilt.
- Reduced the size of the main plugin file without changing the visible feature set.
- Hauptdatei des Plugins verkleinert, ohne den sichtbaren Funktionsumfang zu ändern.

## 0.1.20 (2026-04-06)

### UX
- Split General Settings into System, Layout, and Behavior tabs.
- Allgemeine Einstellungen in System-, Layout- und Verhalten-Tabs aufgeteilt.
- Synced Elementor Boxed Width as token and helper class.
- Elementor Boxed Width als Token und Helferklasse in den Sync aufgenommen.

## 0.1.19 (2026-04-06)

### Fix
- Stopped class sync from overwriting existing Elementor global class values.
- Klassen-Sync überschreibt keine bestehenden Werte globaler Elementor-Klassen mehr.
- Existing class styles stay intact while missing classes are still added.
- Bestehende Klassenstile bleiben erhalten, fehlende Klassen werden weiter ergänzt.

## 0.1.18 (2026-04-06)

### Fix
- Hardened the Elementor sync flow.
- Elementor-Sync robuster gemacht.
- Improved admin-post handling.
- admin-post-Handling verbessert.
- Stabilized previews for spacing, typography, and radius variables.
- Vorschau für Abstands-, Typografie- und Radius-Variablen stabilisiert.

## 0.1.17 (2026-04-06)

### UX
- Refined the class library flow.
- Klassenbibliothek klarer strukturiert.
- Added a guided BEM generator.
- Geführten BEM-Generator ergänzt.
- Improved filters and compact helper states.
- Filter und kompakte Hilfszustände verbessert.

## 0.1.16 (2026-04-05)

### Fix
- Added a canonical plugin-folder fallback for GitHub updates.
- Kanonischen Fallback für den Plugin-Ordner bei GitHub-Updates ergänzt.
- Keeps the installer on `elementor-core-framework`.
- Hält den Installer bei `elementor-core-framework`.

## 0.1.15 (2026-04-05)

### Fix
- Normalized the GitHub update install path.
- Installationspfad für GitHub-Updates normalisiert.
- Keeps the plugin folder as `elementor-core-framework` after updates.
- Behält nach Updates den Plugin-Ordner `elementor-core-framework`.

## 0.1.14 (2026-04-05)

### Feature
- Added separate Elementor class overviews for ECF and foreign classes.
- Eigene Klassenansichten für ECF- und fremde Elementor-Klassen ergänzt.
- Added live counters for variables and classes.
- Live-Zähler für Variablen und Klassen ergänzt.

### UX
- Renamed the utilities navigation label to classes.
- Navigationspunkt Hilfsklassen in Klassen umbenannt.
- Removed the generated example class block.
- Generierten Beispielblock entfernt.

## 0.1.13 (2026-04-04)

### Fix
- Guarded the plugin bootstrap against duplicate class loading.
- Plugin-Bootstrap gegen doppelte Klassendeklaration abgesichert.
- Prevents fatal activation errors after updates.
- Verhindert fatale Aktivierungsfehler nach Updates.

## 0.1.12 (2026-04-04)

### Fix
- Remembers the active plugin state before GitHub updates.
- Merkt sich vor GitHub-Updates den aktiven Plugin-Status.
- Reactivates the plugin automatically after the update.
- Aktiviert das Plugin nach dem Update automatisch wieder.

## 0.1.11 (2026-04-04)

### Change
- Moved Root Font Size out of the Typography panel.
- Root Font Size aus der Typografie herausgezogen.
- Placed it in a more general settings area.
- In einen allgemeineren Einstellungsbereich verschoben.

## 0.1.10 (2026-04-04)

### Change
- Unified output around `--ecf-*` variables and `.ecf-*` classes.
- Ausgabe auf `--ecf-*`-Variablen und `.ecf-*`-Klassen vereinheitlicht.
- Kept legacy `.cf-*` aliases for compatibility.
- Alte `.cf-*`-Aliase für Kompatibilität beibehalten.
- Switched typography, spacing, and radius tokens to rem-based output.
- Typografie-, Abstands- und Radius-Tokens auf rem-basierte Ausgabe umgestellt.

## 0.1.9 (2026-04-04)

### UX
- Typography preview now shows real min/max font sizes.
- Typografie-Vorschau zeigt jetzt echte Min-/Max-Schriftgrößen.
- Replaced the raw clamp readout on the right side.
- Rohe Clamp-Anzeige auf der rechten Seite ersetzt.

## 0.1.8 (2026-04-04)

### Fix
- Improved Elementor sync and cleanup handling.
- Elementor-Sync und Cleanup robuster gemacht.
- Added clearer notices and automatic cache clearing.
- Klarere Meldungen und automatische Cache-Leerung ergänzt.

### Feature
- Added type tabs for ECF and foreign Elementor variables.
- Typ-Tabs für ECF- und fremde Elementor-Variablen ergänzt.
- Makes large variable lists easier to scan.
- Macht große Variablenlisten leichter scanbar.

## 0.1.7 (2026-04-04)

### Feature
- Added selectable color formats for HEX, HEXA, RGB, RGBA, HSL, and HSLA.
- Wählbare Farbformate für HEX, HEXA, RGB, RGBA, HSL und HSLA ergänzt.
- Added GitHub-based plugin update checks with native WordPress support.
- GitHub-basierte Plugin-Update-Prüfung mit nativer WordPress-Unterstützung ergänzt.

### UX
- Color value field now updates the real color live.
- Farbwert-Feld aktualisiert die echte Farbe jetzt live.
- Replaced helper badges with direct hover tooltips.
- Helper-Badges durch direkte Hover-Tooltips ersetzt.
- Cleaned up the admin header alignment.
- Ausrichtung des Admin-Headers bereinigt.
- Expanded the changelog view with version and date history.
- Changelog-Ansicht um Versions- und Datumsverlauf erweitert.

## 0.1.6 (2026-04-03)

### Feature
- Added REST endpoints for live ECF settings access and Elementor sync.
- REST-Endpunkte für den Live-Zugriff auf ECF-Einstellungen und den Elementor-Sync hinzugefügt.
- Added live typography preview inside the Typography tab.
- Live-Typografie-Vorschau im Typography-Tab hinzugefügt.
- Added preset dropdown values for type scale ratios.
- Vordefinierte Dropdown-Werte für Skalierungsverhältnisse der Schriftskala hinzugefügt.
- Added local font file management via the WordPress media library.
- Verwaltung lokaler Schriftdateien über die WordPress-Mediathek hinzugefügt.
- Added automatic `@font-face` output for locally stored font files.
- Automatische `@font-face`-Ausgabe für lokal gespeicherte Schriftdateien hinzugefügt.

### UX
- Reworked the Typography tab layout to include a large visual preview panel.
- Das Layout des Typography-Tabs für ein großes visuelles Vorschau-Panel überarbeitet.
- Reworked the Shadows tab to mirror the Typography UI with a focused live preview and selectable shadow rows.
- Den Schatten-Tab so überarbeitet, dass er die Typography-UI mit fokussierter Live-Vorschau und auswählbaren Schatten-Zeilen spiegelt.
- Narrowed the shadow token name column so shadow values stay visible in tighter admin widths.
- Die Namensspalte der Schatten-Tokens verschmälert, damit die Schattenwerte auch bei engerer Admin-Breite sichtbar bleiben.
- Rebalanced the shadow preview sizing so the main preview is less oversized and the row previews are easier to compare.
- Die Größenverhältnisse der Schattenvorschau angepasst, damit die Hauptvorschau weniger übergroß wirkt und die Zeilenvorschauen besser vergleichbar sind.
- Added visual typography utility showcases for line heights, text modifiers, font weights, and text alignment inside the Typography tab.
- Visuelle Typography-Utility-Ansichten für Zeilenhöhen, Text-Modifikatoren, Schriftstärken und Textausrichtung im Typography-Tab hinzugefügt.
- Updated the utility table label to show `Selector` in English and `Klassenname` in German.
- Die Tabellenüberschrift der Utility-Ansichten so angepasst, dass auf Englisch `Selector` und auf Deutsch `Klassenname` angezeigt wird.
- Updated the shared token table header so standard lists like Colors also use `Klassenname` in German.
- Die gemeinsame Tabellenüberschrift der Token-Listen angepasst, sodass Standardlisten wie Farben auf Deutsch ebenfalls `Klassenname` verwenden.
- Standardized visible `Name` labels and placeholders to `Class Name` / `Klassenname` across the plugin tables.
- Sichtbare `Name`-Beschriftungen und Platzhalter in den Plugin-Tabellen durchgängig auf `Class Name` / `Klassenname` vereinheitlicht.
- Renamed the plugin branding to `ECF Elementor v4 Core Framework` across the main visible backend locations.
- Das Plugin-Branding an den wichtigsten sichtbaren Stellen im Backend auf `ECF Elementor v4 Core Framework` umbenannt.
- Reduced the oversized main shadow preview card so it matches the smaller shadow preview boxes more closely.
- Die zu große Haupt-Schattenvorschau verkleinert, damit sie stärker zu den kleineren Schatten-Preview-Boxen passt.
- Matched the main shadow preview box to the exact dimensions of the smaller shadow preview tiles.
- Die Haupt-Schattenvorschau auf exakt dieselben Maße wie die kleineren Schatten-Preview-Kacheln gebracht.
- Replaced the indirect typography scale factor control with direct `Min Font Size` and `Max Font Size` inputs.
- Die indirekte Steuerung per Skalierungsfaktor in der Typografie durch direkte Eingaben für `Min Font Size` und `Max Font Size` ersetzt.
- Added separate `Min Scale Ratio` and `Max Scale Ratio` controls so the minimum and maximum type scales can be tuned independently.
- Separate Eingaben für `Min Scale Ratio` und `Max Scale Ratio` ergänzt, damit sich minimale und maximale Schriftskala unabhängig voneinander steuern lassen.
- Set the default typography base values to `16` for minimum and `18` for maximum font size.
- Die Standardwerte der Typografie-Basis auf `16` für die minimale und `18` für die maximale Schriftgröße gesetzt.
- Added device icons to the typography preview so `Minimum` shows a phone icon and `Maximum` shows a desktop icon.
- Der Typografie-Vorschau Geräte-Icons hinzugefügt, sodass `Minimum` ein Handy-Icon und `Maximum` ein Desktop-Icon zeigt.
- Extended the minimum/maximum device icons to the remaining typography controls and state labels for consistent UI.
- Die Geräte-Icons für Minimum und Maximum auf die übrigen Typografie-Steuerelemente und Status-Labels erweitert, damit die UI konsistent bleibt.
- Restricted local font usage to files hosted on the same site inside the WordPress uploads directory.
- Die Nutzung lokaler Schriften auf Dateien derselben Website im WordPress-Uploads-Ordner beschränkt.
- Hardened sanitization for CSS-related settings such as font stacks, sizes, shadows, weights, and tracking values.
- Die Sanitization für CSS-relevante Einstellungen wie Font-Stacks, Größen, Schatten, Gewichte und Tracking-Werte gehärtet.

### Security
- Added stricter security checks for framework management actions and REST access.
- Strengere Sicherheitsprüfungen für Framework-Aktionen und REST-Zugriffe hinzugefügt.
- Limited sensitive plugin actions to users with both `manage_options` and `activate_plugins`.
- Sensible Plugin-Aktionen auf Nutzer mit `manage_options` und `activate_plugins` beschränkt.
- Scoped font MIME allowances to authorized admin usage.
- Freigaben für Font-MIME-Typen auf autorisierte Admin-Nutzung begrenzt.
- Prevented arbitrary external font URLs from being stored as local font sources.
- Verhindert, dass beliebige externe Font-URLs als lokale Schriftquellen gespeichert werden.
