Variable Naming Conventions

-Standard Variable, name to best describe the content, camelcase. Attach ewim_ at the front to ensure uniqueness
$ewim_standardVariable= "Content";

-Arrays, small a to indicate as an array, camelcase the rest. Attach ewim_ at the front to ensure uniqueness
$ewim_aArray= array();

-Objects, underscored to match with the object functions, and to make it easy to identify an object in the code. Attach ewim_ at the front to ensure uniqueness
-Can Also use lowercase o in front of a variable to denote an object, use this when the object name does not fit in the code method
$ewim_object_variable= object;
$ewim_oObjectVariable= object;

-Table Name, camelcase, regardless of the table name. Attach ewim_ at the front to ensure uniqueness
$ewim_tTableName= "table_name";

Forms

-Location Form
This form should be able to accept the customer name and ID. ID should be hidden.
Upon submission, the id should be verified in a pre hook



Note on GF
To populate checkbox with url, do this: parameter= this must match the value of the check box exactly, including caps and lower case.