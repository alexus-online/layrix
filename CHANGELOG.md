# Changelog

## 0.1.10 (2026-04-04)

### Changed
- Prepared a minimal release to test GitHub-based plugin update detection in the WordPress plugin overview.
- Minimalen Release vorbereitet, um die GitHub-basierte Plugin-Update-Erkennung in der WordPress-Pluginübersicht zu testen.

## 0.1.7 (2026-04-04)

### Added
- Added selectable color value formats for HEX, HEXA, RGB, RGBA, HSL, and HSLA in the color token table.
- Wählbare Farbwert-Formate für HEX, HEXA, RGB, RGBA, HSL und HSLA in der Farbtoken-Tabelle hinzugefügt.
- Added automatic GitHub-based plugin update checks with native WordPress update support.
- Automatische GitHub-basierte Plugin-Update-Prüfung mit nativer WordPress-Update-Unterstützung hinzugefügt.

### Changed
- Updated the color value field so editing the displayed color format updates the actual color live.
- Das Farbwert-Feld so erweitert, dass Eingaben im gewählten Farbformat die echte Farbe live aktualisieren.
- Replaced question-mark helper badges with direct hover tooltips on labels and table headers across colors, typography, spacing, and related token tables.
- Fragezeichen-Hinweise durch direkte Hover-Tooltips auf Labels und Tabellenköpfen in Farben, Typografie, Abständen und verwandten Token-Tabellen ersetzt.
- Improved the admin layout alignment so the plugin header sits cleaner and the unwanted white strip on the left is removed.
- Das Admin-Layout so angepasst, dass der Plugin-Kopf sauberer sitzt und die unerwünschte weiße Leiste links verschwindet.
- Expanded the version changelog view so entries are shown as versioned history with version number and date.
- Die Versions-Changelog-Ansicht erweitert, sodass Einträge als Versionshistorie mit Versionsnummer und Datum erscheinen.

## 0.1.6 (2026-04-03)

### Added
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
- Added stricter security checks for framework management actions and REST access.
- Strengere Sicherheitsprüfungen für Framework-Aktionen und REST-Zugriffe hinzugefügt.

### Changed
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
- Limited sensitive plugin actions to users with both `manage_options` and `activate_plugins`.
- Sensible Plugin-Aktionen auf Nutzer mit `manage_options` und `activate_plugins` beschränkt.
- Scoped font MIME allowances to authorized admin usage.
- Freigaben für Font-MIME-Typen auf autorisierte Admin-Nutzung begrenzt.
- Prevented arbitrary external font URLs from being stored as local font sources.
- Verhindert, dass beliebige externe Font-URLs als lokale Schriftquellen gespeichert werden.
