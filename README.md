# co2avarage
<p>The charts are displayed on the form confirmation page using separate shortcodes for each field https://prnt.sc/i-UfeNEWo8Hq</p>
<p>The shortcode format is as follows:</p>
<code>[average field-id=51 total="{Company CO2 Emissions (tons):51:value}"]</code>
<p>
In this shortcode, you need to specify the field ID and the corresponding field value in the total attribute. The shortcode will then generate a chart representing the average value.
</p>
<p>
Currently, the plugin is functioning for forms with IDs 2 and 7:
</p><p>
Form ID 2 corresponds to the individual form.<br />
Form ID 7 corresponds to the small business form.<br />
For displaying the charts in the PDF templates for the form, we have used a different shortcode:</p>
<code>[getChart field-id="field ID" form-id="form ID" total="field value"]</code>
<p>
To include this shortcode in the PDF template, you can use the following code snippet:</p>
<code>echo do_shortcode('[getChart field-id="8" form-id="2" total="' . $entry[8] . '" ]') </code>
<p>
The plugin supports multilingual functionality, and you can add more languages using the Loco Translate plugin.</p>
