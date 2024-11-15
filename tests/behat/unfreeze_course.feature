@block @block_lifecycle

Feature: Unfreeze a frozen course
  As a teacher with the appropriate permission
  I can click on the "Enable editing" button in the lifecycle block to unfreeze a frozen course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | idnumber | email                |
      | teacher1 | Teacher1  | Test     | tea1     | teacher1@example.com |
    And the following custom field exists for lifecycle block:
      | category  | CLC |
      | shortname | course_year |
      | name      | Course Year |
      | type      | text        |
    And the following "courses" exist:
      | fullname | shortname | format | customfield_course_year | startdate        | enddate         |
      | Course 1 | C1        | topics | ##now##%Y##             | ## 2 days ago ## | ## yesterday ## |
    And the following "course enrolments" exist:
      | user     | course | role |
      | teacher1 | C1     | editingteacher |
    And the following "blocks" exist:
      | blockname | contextlevel | reference | pagetypepattern | defaultregion |
      | lifecycle | Course       | C1        | course-view-*   | side-pre      |
    And the "C1" "Course" is context frozen

  @javascript
  Scenario: Unfreeze a frozen course
    Given I am on the "C1" course page logged in as teacher1
    And edit mode should not be available on the current page
    And I should see "Enable editing" in the "Lifecycle" "block"
    And I click on "Enable editing" "text"
    And I press "Confirm"
    Then edit mode should be available on the current page
