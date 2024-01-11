# moodle-block_lifecycle
This block aims to show the course lifecycle information and provide a convenience way to override the course context freezing settings.

It relies on a custom course field defining the academic year of the course (e.g. '2023' for the 2023-24 academic year). This is populated by our course rollover plugin but could be populated through other means.

Uses the later of Late Summer Assessment end date + 12 weeks or Course End Date + 12 weeks or End of Academic Year + 12 weeks in order to apply core's "context freezing" to that course through a nightly scheduled task. And provides controls over delaying read-only or preventing it outright - these options are only displayed after the course end date has passed.

The number of weeks is configurable in site settings.
