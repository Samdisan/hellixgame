schema.md — JSON Schema для HELIX ECHELON

Цей документ описує усі JSON-файли в /data:

структура

ключі

типи

обов’язковість полів

базові приклади

1. players.json

Призначення: список усіх персонажів гри.

Тип файлу: масив об’єктів.

[
  {
    "id": "PL_FARRAGUT",      // string, унікальний ID гравця
    "name": "Д-р Алан Фарраґут", // string, повне ім'я/позивний
    "faction": "station",     // string: "station" | "who" | "ilaria"
    "role": "Головний інфекціоніст", // string, коротка роль (опц.)
    "access_level": 1,        // integer 1–5
    "status": "active"        // string: "search" | "active" | "quarantine" | "unknown"
  }
]


Обов’язкові поля: id, name, faction, access_level, status
Опційні: role

2. access-codes.json

Призначення: коди входу для гравців та адмінів.

Тип: масив об’єктів.

[
  {
    "code": "A1B2C3",         // string, унікальний код
    "type": "player",         // string: "player" | "admin"
    "player_id": "PL_FARRAGUT"// string, обов'язковий якщо type="player"
  },
  {
    "code": "MASTER2026",
    "type": "admin"
  }
]

3. phases.json

Призначення: інформація про фази гри.

{
  "current_phase": "PH_INTRO",    // string, id активної фази
  "phases": [
    {
      "id": "PH_INTRO",           // string, унікальний ID фази
      "title": "Прибуття експедиції", // string, назва
      "description": "Початкова фаза гри...", // string
      "order": 1,                 // integer, порядок
      "ui_intensity": "low",      // string: "low" | "medium" | "high" | "critical"
      "hint_frequency": "low",    // string: "low" | "medium" | "high"
      "on_start_quests": [        // array of quest_id, опц.
        "Q_INTRO_MESSAGE"
      ],
      "on_end_quests": [          // array of quest_id, опц.
        "Q_START_OUTBREAK"
      ]
    }
  ]
}


Обов’язкові: current_phase, phases[].id, phases[].title, phases[].order
Решта — за бажанням, але дуже бажані.

4. timer.json

Призначення: глобальний таймер гри + тригери часу.

{
  "state": "running",         // string: "not_started" | "running" | "paused" | "finished"
  "duration_sec": 43200,      // integer, загальна тривалість гри (12 годин)
  "start_time": 1737650400,   // integer (UNIX timestamp), коли стартував таймер
  "elapsed_sec": 900,         // integer, скільки секунд пройшло (може обчислюватись)
  "time_triggers": [
    {
      "id": "TT_INTRO_END",   // string, унікальний ID тригера
      "quest_id": "Q_START_OUTBREAK", // string, який квест запускати
      "elapsed_ge_sec": 900,  // integer, умова: elapsed >= 900 сек
      "remaining_le_sec": null, // integer | null, умова по залишку
      "fired": false          // bool, вже виконався чи ні
    }
  ]
}


Примітки:

Тригер може мати або elapsed_ge_sec, або remaining_le_sec, або обидва.

Якщо умова виконана і fired = false → запускаємо квест → ставимо fired = true.

5. quests.json

Призначення: опис усіх квестів / сюжетних подій.

Тип: масив об’єктів.

[
  {
    "id": "Q_START_OUTBREAK",     // string, унікальний ID
    "label": "Запуск фази OUTBREAK", // string, коротка назва
    "description": "Станція переходить у фазу перших симптомів.", // string, опц.
    "phase": "PH_INTRO",          // string, до якої фази належить
    "subphase": null,             // string | null, якщо це підфаза
    "actions": [                  // масив дій
      {
        "type": "set_phase",      // string, тип дії
        "to": "PH_OUTBREAK"       // параметри для цього типу
      },
      {
        "type": "push_terminal",
        "message_id": "MSG_OUTBREAK_START"
      }
    ]
  }
]


Можливі типи actions.type:

"set_phase" — перемикає фазу.

{"type": "set_phase", "to": "PH_OUTBREAK"}

"activate_protocol" — робить протокол активним.

{"type": "activate_protocol", "protocol_id": "P_MED_01"}

"deactivate_protocol"

{"type": "deactivate_protocol", "protocol_id": "P_MED_02"}

"unlock_protocol" — робить протокол видимим для гравців.

{"type": "unlock_protocol", "protocol_id": "P_SEC_01"}

"set_access" — змінює рівень доступу гравця.

{"type": "set_access", "player_id": "PL_FARRAGUT", "level": 3}

"set_status" — змінює статус гравця.

{"type": "set_status", "player_id": "PL_FARRAGUT", "status": "quarantine"}

"ui_mode" — змінює режим UI (колір/інтенсивність).

{"type": "ui_mode", "mode": "red"}

"push_terminal" — додає повідомлення до термінала.

{"type": "push_terminal", "message_id": "MSG_OUTBREAK_START"}

"custom" — резерв під щось особливе.

{"type": "custom", "code": "..."}

6. protocols.json

Призначення: всі протоколи станції (інструкції, накази, звіти).

Тип: масив об’єктів.

[
  {
    "id": "P_MED_01",             // string, унікальний ID
    "label": "Початковий медичний протокол", // string, коротка назва
    "phase": "PH_OUTBREAK",       // string, базова фаза
    "level": 1,                   // integer 1–5, мінімальний доступ
    "public": true,               // bool, публічний чи ні
    "active": true,               // bool, діє зараз
    "publish_time": "00:30:00",   // string, час гри (HH:MM:SS) коли з'являється (опц.)
    "text": "Повний текст протоколу...", // string
    "flags": {
      "critical": false,          // bool
      "hazard": false             // bool
    }
  }
]


Примітка:

publish_time може використовуватись як інформаційне поле або для логіки відображення.

Відкритість гравцю = active && (player.access_level >= level) + опц. фільтри.

7. terminal-messages.json

Призначення: стрічка подій термінала.

Тип: масив об’єктів.

[
  {
    "id": "MSG_OUTBREAK_START",   // string, унікальний ID
    "time_game": "00:15:00",      // string (HH:MM:SS), ігровий час
    "type": "warning",            // string: "info" | "warning" | "critical" | "protocol" | "system"
    "target": "public_terminal",  // string: "public_terminal" | "admin_terminal" | "both"
    "text": "Система фіксує перші симптоми аномалій...", // string
    "source": "system",           // string: "system" | "gm" | "who" | "ilaria" | ...
    "related_id": null            // string | null: може бути quest_id чи protocol_id
  }
]

8. hints.json

Призначення: атмосферні підказки/фрази для глічів.

Тип: масив об’єктів.

[
  {
    "id": "HINT_VENT",            // string
    "text": "Система вентиляції працює зі збоєм.", // string
    "context": "any",             // string: "any" | "intro" | "outbreak" | "quarantine" | "final"
    "weight": 1                   // integer, частота показу
  }
]

9. player-progress.json

Призначення: що вже переглянув / отримав конкретний гравець.

{
  "PL_FARRAGUT": {
    "opened_protocols": [ "P_MED_01", "P_SEC_01" ], // масив protocol_id
    "seen_messages": [ "MSG_OUTBREAK_START" ]       // масив message_id (опц.)
  },
  "PL_SATO": {
    "opened_protocols": [],
    "seen_messages": []
  }
}

10. time_triggers.json (опціонально, якщо не всередині timer.json)

Якщо вирішимо винести тригери в окремий файл.

[
  {
    "id": "TT_INTRO_END",
    "quest_id": "Q_START_OUTBREAK",
    "elapsed_ge_sec": 900,
    "remaining_le_sec": null,
    "fired": false
  }
]

11. Можливі додаткові файли (future)

scenes.json / phases-extended.json — якщо захочемо описувати сценарії ще детальніше.
