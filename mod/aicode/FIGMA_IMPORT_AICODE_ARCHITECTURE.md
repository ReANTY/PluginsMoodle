# Import Diagram AICode ke Figma

File diagram yang sudah disiapkan:

1. `aicode-ltsa-architecture.svg`
2. `aicode-system-architecture.svg`

## Cara import (editable)

1. Buka file Figma kamu.
2. Drag and drop kedua file `.svg` ke canvas Figma.
3. Pilih object hasil import, lalu `Ungroup` jika ingin edit per shape.
4. Ubah warna, font, atau label sesuai kebutuhan laporanmu.

## Struktur yang sudah dipetakan

- `aicode-ltsa-architecture.svg`
  - Komponen LTSA inti: Learner Entity, Delivery, Evaluation, Coach, Learning Resources, Learner Record.
  - Komponen tambahan plugin: AI Feedback Engine, Executor, Hint Tracker, Prompt Cache, Teacher Review.

- `aicode-system-architecture.svg`
  - Layer arsitektur: Actor, Browser Frontend, Moodle Plugin Server, Database, External Services.
  - Alur utama: run code, analyze code, record hint, send to teacher, integrasi AI provider, dan eksekusi sandbox.

## Catatan

- Kedua file ini berbasis vektor, jadi tetap tajam saat di-resize.
- Panah dan shape bisa kamu adjust langsung di Figma.
