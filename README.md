# co2avarage
The charts are displayed on the form confirmation page using separate shortcodes for each field https://prnt.sc/i-UfeNEWo8Hq
The shortcode format is as follows:
[average field-id=51 total="{Company CO2 Emissions (tons):51:value}"]

In this shortcode, you need to specify the field ID and the corresponding field value in the total attribute. The shortcode will then generate a chart representing the average value.

Currently, the plugin is functioning for forms with IDs 2 and 7:

Form ID 2 corresponds to the individual form.
Form ID 7 corresponds to the small business form.
For displaying the charts in the PDF templates for the form, we have used a different shortcode:
[getChart field-id="field ID" form-id="form ID" total="field value"]

To include this shortcode in the PDF template, you can use the following code snippet:
<?php echo do_shortcode('[getChart field-id="8" form-id="2" total="' . $entry[8] . '" ]') ?>

The plugin supports multilingual functionality, and you can add more languages using the Loco Translate plugin.
