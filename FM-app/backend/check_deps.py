try:
    import bs4
    print("bs4: available")
except ImportError:
    print("bs4: missing")

try:
    import pypdf
    print("pypdf: available")
except ImportError:
    try:
        import PyPDF2
        print("PyPDF2: available")
    except ImportError:
        print("pypdf/PyPDF2: missing")
