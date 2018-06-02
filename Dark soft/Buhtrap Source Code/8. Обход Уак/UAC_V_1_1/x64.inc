IMAGE_OPTIONAL_HEADER64 STRUCT
  Magic                         WORD       ?
  MajorLinkerVersion            BYTE       ?
  MinorLinkerVersion            BYTE       ?
  SizeOfCode                    DWORD      ?
  SizeOfInitializedData         DWORD      ?
  SizeOfUninitializedData       DWORD      ?
  AddressOfEntryPoint           DWORD      ?
  BaseOfCode                    DWORD      ?
  ImageBase                     QWORD      ?
  SectionAlignment              DWORD      ?
  FileAlignment                 DWORD      ?
  MajorOperatingSystemVersion   WORD       ?
  MinorOperatingSystemVersion   WORD       ?
  MajorImageVersion             WORD       ?
  MinorImageVersion             WORD       ?
  MajorSubsystemVersion         WORD       ?
  MinorSubsystemVersion         WORD       ?
  Win32VersionValue             DWORD      ?
  SizeOfImage                   DWORD      ?
  SizeOfHeaders                 DWORD      ?
  CheckSum                      DWORD      ?
  Subsystem                     WORD       ?
  DllCharacteristics            WORD       ?
  SizeOfStackReserve            QWORD      ?
  SizeOfStackCommit             QWORD      ?
  SizeOfHeapReserve             QWORD      ?
  SizeOfHeapCommit              QWORD      ?
  LoaderFlags                   DWORD      ?
  NumberOfRvaAndSizes           DWORD      ?
  DataDirectory                 IMAGE_DATA_DIRECTORY IMAGE_NUMBEROF_DIRECTORY_ENTRIES dup(<>)
IMAGE_OPTIONAL_HEADER64 ENDS

IMAGE_FILE_HEADER64 STRUCT
  Machine               WORD    ?
  NumberOfSections      WORD    ?
  TimeDateStamp         DWORD   ?
  PointerToSymbolTable  DWORD   ?
  NumberOfSymbols       DWORD   ?
  SizeOfOptionalHeader  WORD    ?
  Characteristics       WORD    ?
IMAGE_FILE_HEADER64 ENDS

IMAGE_NT_HEADERS64 STRUCT
  Signature         DWORD                   ?
  FileHeader        IMAGE_FILE_HEADER       <>
  OptionalHeader    IMAGE_OPTIONAL_HEADER64 <>
IMAGE_NT_HEADERS64 ENDS