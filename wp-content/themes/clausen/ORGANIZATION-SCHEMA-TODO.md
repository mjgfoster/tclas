# Organization Schema Enhancement

This document tracks what information needs to be added to the TCLAS Organization schema for better search engine visibility.

## Current Status
The Organization schema is implemented in `inc/template-functions.php` (function `tclas_json_ld()`), but several important fields are currently commented out pending stakeholder input.

## Required Information to Collect

### Contact Details
- **Phone Number** (`telephone`): Main contact phone number for TCLAS
  - Format: E.164 (e.g., `+1-612-XXX-XXXX`)
- **Email Address** (`email`): Primary contact email
  - Should be a general inquiry email, not personal

### Organization Address (`address`)
Complete postal address helps with local search and location-based queries:
- **Street Address**: Street number and name
- **City** (`addressLocality`): City name
- **State** (`addressRegion`): State (MN for Minnesota)
- **ZIP Code** (`postalCode`): 5-digit code
- **Country** (`addressCountry`): Country code (US)

### Founders Information (optional)
- **Founder Names** (`founder`): If applicable, names of TCLAS founders

### Geographic Service Area (`areaServed`)
Helps search engines understand your organization serves specific regions:
- Should include both "US" and "LU" (Luxembourg)
- Could be more specific (e.g., states/regions)

### Board Members (`member`)
Connect your leadership to the organization using Person schema:
- Each board member can be represented as a Person object
- Can reference the board members listed on the About page
- Helps Google Knowledge Graph connect people to your organization

## Implementation Steps

1. **Gather Information**
   - Confirm phone and email to publish
   - Get complete mailing address
   - Document founders (if relevant)
   - Confirm service areas

2. **Update the Schema**
   - Uncomment the TODO fields in `inc/template-functions.php`
   - Fill in actual values
   - For board members, consider querying the `tclas_board` post type

3. **Test the Schema**
   - Use [Google Rich Results Test](https://search.google.com/test/rich-results)
   - Validate with [Schema.org Validator](https://validator.schema.org/)

## Reference
- [Schema.org Organization](https://schema.org/Organization)
- [Schema.org Person](https://schema.org/Person)
- [Google Search Central - Organization Markup](https://developers.google.com/search/docs/appearance/structured-data/organization)

## Contact
When information is finalized, update the code in `inc/template-functions.php` and commit with a note about stakeholder approval.
