# View kanban bootstrap

This module streamlines the process of organizing and visualizing your tasks,
allowing you to effectively manage projects using the Scrum methodology.

## How to use:
you can create a content type with a status field,
which can be a
- Taxonomy field
- List field
- State machine
- Content Moderation(fully compatible)
- Workflow.

You also have the option to include a progress field,
which is a numeric field with a value between 0 and 100,

You can add an assignor field that references the user.

Additionally, you can include a history field,
which can be an unlimited storage setting
- Plain text
- Double field with a datetime (datetime - text).
- Triple field with a date(datetime) - Username(text) - Status(text).

Create a view with style Format "Kanban". Change Show to Fields.
You can add fields you want, Exclude from display the field
if you don't want show (like history field).
and define the field selected the Format / Settings.
In the format settings, you can select your preferred fields,
with the **status field being required** and the others being optional.

This module also supports [notifications via email or Firebase](https://www.drupal.org/project/pwa_firebase),
which can be sent to assignors when the status of a task has changed.
The module design base on a [Bootstrap 5 theme](https://www.drupal.org/project/bootstrap5_admin).

#### Custom JS
You can detect an event when the task has changed status with
```
document.addEventListener("viewsKanban", function(event) {
  console.log("Custom event received:", event.detail);
});
```
event.detail:
- view_id: View id,
- display_id: Display current id,
- entityId: Entity id,
- state: Current State,
- to: New state,
- total: Total of field total,
- point: Total of field point
