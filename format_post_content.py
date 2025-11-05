import sys
import re

def format_content(content):
    # Split the content into lines
    lines = content.splitlines()
    
    # Initialize formatted content
    formatted_content = ""
    
    # Process each line
    for line in lines:
        # Strip leading/trailing whitespace
        line = line.strip()
        
        if not line:
            continue
            
        # Check for headings (e.g., "2) The 3 WordPress Caching Layers")
        if re.match(r'^\d+\)', line):
            # Extract the heading text
            heading_text = re.sub(r'^\d+\)\s*', '', line)
            formatted_content += f"<h2>{heading_text}</h2>\n"
        elif "________________" in line:
            formatted_content += f"<hr />\n"
        else:
            # Check for list items that start with an asterisk
            if line.startswith('*'):
                # Remove the asterisk and strip whitespace
                list_item = line[1:].strip()
                formatted_content += f"<li>{list_item}</li>\n"
            else:
                formatted_content += f"<p>{line}</p>\n"
            
    return formatted_content

if __name__ == "__main__":
    if len(sys.argv) != 3:
        print("Usage: python format_post_content.py <input_file> <output_file>")
        sys.exit(1)
        
    input_file = sys.argv[1]
    output_file = sys.argv[2]
    
    with open(input_file, 'r') as f:
        content = f.read()
        
    # Add <ul> tags around list items
    content = re.sub(r'(<li>.*</li>)', r'<ul>\1</ul>', content, flags=re.DOTALL)

    formatted_content = format_content(content)
    
    with open(output_file, 'w') as f:
        f.write(formatted_content)
