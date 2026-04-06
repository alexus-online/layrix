# Changelog

## 0.1.18 (2026-04-06)

### Fixed
- Hardened the Elementor sync flow, improved admin-post handling, and stabilized variable previews for spacing, typography, and radius values.
- Den Elementor-Sync robuster gemacht, das admin-post-Handling verbessert und die Variablen-Vorschau für Abstände, Typografie und Radius stabilisiert.

## 0.1.17 (2026-04-06)

### Changed
- Refined the class library and overall admin UX with cleaner class flows, a guided BEM generator, improved filters, and compact helper states.
- Die Klassenbibliothek und die gesamte Admin-UX mit klareren Klassen-Flows, geführtem BEM-Generator, verbesserten Filtern und kompakteren Hilfszuständen verfeinert.

## 0.1.16 (2026-04-05)

### Fixed
- Added a canonical plugin-folder fallback for GitHub updates so the installer should keep using `elementor-core-framework` instead of drifting to `elementor-core-framework-master`.
- Einen kanonischen Fallback fuer den Plugin-Ordner bei GitHub-Updates ergaenzt, damit der Installer bei `elementor-core-framework` bleibt und nicht wieder auf `elementor-core-framework-master` springt.

## 0.1.15 (2026-04-05)

### Fixed
- Normalized the GitHub update install path so the plugin keeps the folder name `elementor-core-framework` after updates instead of falling back to `elementor-core-framework-master`.
- Den Installationspfad fuer GitHub-Updates normalisiert, damit das Plugin nach Updates den Ordnernamen `elementor-core-framework` behaelt und nicht auf `elementor-core-framework-master` springt.

## 0.1.14 (2026-04-05)

### Changed
- Added dedicated Elementor class overviews in the variables area for ECF and foreign classes, including live counters for variables and classes.
- Eigene Elementor-Klassenansichten im Variablen-Bereich fuer ECF- und fremde Klassen hinzugefuegt, inklusive Live-Zaehlern fuer Variablen und Klassen.
- Renamed the utilities navigation label to classes and removed the generated example class block from that view.
- Den Navigationspunkt Hilfsklassen in Klassen umbenannt und den generierten Beispielblock aus dieser Ansicht entfernt.

## 0.1.13 (2026-04-04)

### Fixed
- Guarded the plugin bootstrap so the main class is only declared and instantiated once, preventing fatal activation errors after updates.
- Den Plugin-Bootstrap abgesichert, damit die Hauptklasse nur einmal deklariert und instanziiert wird und keine fatalen Aktivierungsfehler nach Updates mehr entstehen.

## 0.1.12 (2026-04-04)

### Fixed
- Remember the active plugin state before a GitHub update and reactivate the plugin automatically after the update completes.
- Merkt sich vor einem GitHub-Update den aktiven Plugin-Status und aktiviert das Plugin nach dem Update automatisch wieder.

## 0.1.11 (2026-04-04)

### Changed
- Moved the root font size control out of the typography panel into a more general settings area because it affects typography, spacing, and radius together.
- Die Einstellung fuer die Root Font Size aus der Typografie in einen allgemeineren Einstellungsbereich verschoben, weil sie Typografie, Abstaende und Radius gemeinsam beeinflusst.

## 0.1.10 (2026-04-04)

### Changed
- Unified the plugin output around `--ecf-*` variables and `.ecf-*` utility classes while keeping legacy `.cf-*` aliases for compatibility.
- Die Plugin-Ausgabe auf `--ecf-*`-Variablen und `.ecf-*`-Hilfsklassen vereinheitlicht und die alten `.cf-*`-Aliase fuer Kompatibilitaet beibehalten.
- Switched token generation for typography, spacing, and radius to rem-based output with a configurable root font size and px-only preview readouts.
- Die Token-Erzeugung fuer Typografie, Abstaende und Radius auf rem-basierte Ausgabe mit konfigurierbarer Root Font Size und px-Anzeige in der Vorschau umgestellt.

## 0.1.9 (2026-04-04)

### Changed
- Updated the typography preview so the right side shows the real minimum and maximum font sizes instead of the raw clamp value.
- Die Typografie-Vorschau so angepasst, dass rechts die echten minimalen und maximalen Schriftgroessen statt des rohen Clamp-Werts angezeigt werden.

## 0.1.8 (2026-04-04)

### Changed
- Improved the Elementor sync and cleanup workflow with clearer notices, automatic cache clearing, and more resilient cleanup handling.
- Den Elementor-Sync- und Cleanup-Ablauf mit klareren Meldungen, automatischer Cache-Leerung und robusterem Cleanup verbessert.
- Added type tabs for ECF and foreign Elementor variables to make large variable lists easier to scan.
- Typ-Tabs fuer ECF- und fremde Elementor-Variablen hinzugefuegt, damit grosse Variablenlisten leichter zu ueberblicken sind.

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
