# Example Instruction File - KKF Extract Validation

Document type: KKF extract (company registry document)
Version: 0.1

## Required Fields
- Company name
- KVK/KKF number
- Issue date
- Registered address

## Validation Rules
- Issue date must be within the last 12 months.
- KVK/KKF number must be present and readable.
- Company name must be present and readable.

## Output Expectations (Gemini Summary)
- Findings: what was detected with confidence.
- Missing: required fields not found or unclear.
- Improvements: suggestions to improve scan quality (lighting, resolution, crop).
