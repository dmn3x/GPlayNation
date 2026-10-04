# GPlayNation Design Tokens

Extracted from Figma file `GPlayNation` (node `358:2`) via MCP `get_design_context`,
parsing the full node tree of all 12 screens at depth 14.

## Surfaces

| Token | Hex | Usage |
|---|---|---|
| `--bg` | `#0F0F10` | app / screen background |
| `--bg-raised` | `#161618` | sidebar, panel, card |
| `--bg-elevated` | `#222224` | input fields, hover chips, table rows |
| `--chrome` | `#161618` | macOS-style title bar |
| `--border` | `#222224` | 1px hairline dividers |
| `--border-strong` | `#27282A` | explicit 1px card borders |

## Text

| Token | Value | Usage |
|---|---|---|
| `--fg` | `#FFFFFF` | primary text |
| `--fg-muted` | `#FFFFFF` @ 60% | body copy, secondary labels |
| `--fg-subtle` | `#FFFFFF` @ 50% | nav items, placeholders |
| `--fg-faint` | `#FFFFFF` @ 40% | meta, timestamps |
| `--fg-ghost` | `#FFFFFF` @ 30% | disabled |
| `--fg-blue` | `#60A5FA` | links |
| `--danger` | `#E7000B` | Archive / destructive |
| `--success` | `#30D158` | positive delta badge text |
| `--success-dim` | `#30D158` @ 80% | green traffic dot |
| `--warn-dim` | `#FF9F0A` @ 80% | amber traffic dot |
| `--error-dim` | `#FF453A` @ 80% | red traffic dot |

Traffic dots share a 1px stroke in the same colour (`#FF453A`/`#FF9F0A`/`#30D158`).

## Accent

| Token | Value | Usage |
|---|---|---|
| `--accent` | `#1D4ED8` | primary button fill, active nav, chart line |
| `--accent-hi` | `#287BF7` | chart gradient top, glow |
| `--accent-10` | `#1D4ED8` @ 10% | active nav pill background |
| `--accent-15` | `#1D4ED8` @ 15% | chart area fill |
| `--accent-border` | `#1D4ED8` 1.67px | focused input ring |

## Radii

`4` (tag/chip) · `8` (small button, select) · `10` (input, small card) · `12` (button,
card) · `16` (screen shell, panel, hero). Pills and circles use `9999`.

## Type scale — Inter

| Role | Size | Weight | Line height | Letter spacing |
|---|---|---|---|---|
| Screen title | `27px` | SemiBold | `37.8px` | `-0.675px` |
| Section heading | `18.9px` | SemiBold | `27px` | `0` |
| Body / input | `16.2px` | Regular | `21.6px` | `0` |
| Nav / button | `16.2px` | Medium | `21.6px` | `0` |

## Elevation

| Token | Value |
|---|---|
| `--shadow-screen` | `0 25px 50px -12px rgb(0 0 0 / .25)` |
| `--shadow-card` | `0 8px 10px -6px rgb(0 0 0 / .2)` |
| `--shadow-pop` | `0 3px 6px rgb(0 0 0 / .2)` |
| `--glow-accent` | `0 0 15px 0 #1D4ED8` |
| `--glow-accent-lg` | `0 0 30px 0 #1D4ED8` |

## Layout

- Screen shell `1512 × 982`, radius `16`, 1px `--border`, `--shadow-screen`.
- Title bar `48px` tall (`--chrome`), 3 traffic dots `12px`, 8px gap, 20px side padding.
- App nav `147px` tall, 48px side padding.
- Store sidebar `220px` wide.
- Chart: 1.5px line, `--accent`, area fill `--accent-15` → transparent.