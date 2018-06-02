
format PE GUI 4.0
entry main

include 'win32ax.inc'

section '.text' code readable executable

  proc main
    locals
      hSrcFile dd ?
      hDstFile dd ?
      lSrcData dd ?
      lDstData dd ?
      pSrcData dd ?
      pDstData dd ?
      BytesCount dd ?
    endl
	invoke	CreateFile,_source_file_path,GENERIC_READ,FILE_SHARE_READ,NULL,OPEN_EXISTING,FILE_ATTRIBUTE_NORMAL,NULL
	.if	~(eax=-1)
		mov	[hSrcFile],eax
		invoke	SetFilePointer,[hSrcFile],0,NULL,FILE_END
		.if	~(eax=-1)&(eax)
			mov	[lSrcData],eax
			invoke	LocalAlloc,LMEM_FIXED,[lSrcData]
			.if	eax
				mov	[pSrcData],eax
				invoke	SetFilePointer,[hSrcFile],0,NULL,FILE_BEGIN
				.if	~(eax=-1)
					invoke	ReadFile,[hSrcFile],[pSrcData],[lSrcData],addr BytesCount,NULL
					.if	(eax)
						mov	eax,[lSrcData]
						shl	eax,1
						invoke	LocalAlloc,LMEM_FIXED,eax
						.if	eax
							mov	[pDstData],eax
							stdcall encode_text_variables,[pDstData],[pSrcData],[lSrcData]
							.if	eax
								mov	[lDstData],eax
								invoke	CreateFile,_output_file_path,GENERIC_WRITE,FILE_SHARE_READ,NULL,CREATE_ALWAYS,FILE_ATTRIBUTE_NORMAL,NULL
								.if	~(eax=-1)
									mov	[hDstFile],eax
									invoke	SetFilePointer,[hDstFile],0,NULL,FILE_BEGIN
									.if	~(eax=-1)
										invoke	WriteFile,[hDstFile],[pDstData],[lDstData],addr BytesCount,NULL
									.endif
									invoke	CloseHandle,[hDstFile]
								.endif
							.endif
							invoke	LocalFree,[pDstData]
						.endif
					.endif
				.endif
				invoke	LocalFree,[pSrcData]
			.endif
		.endif
		invoke	CloseHandle,[hSrcFile]
	.endif
	invoke	ExitProcess,0
  endp

  proc encode_text_variables uses ebx esi edi,pDstData,pSrcData,lSrcData
    locals
      lDstData dd ?
    endl
	xor	eax,eax
	mov	[lDstData],eax
	mov	esi,[pSrcData]
	mov	edi,[pDstData]
	mov	ebx,[lSrcData]
	.while	(ebx)
		dec	ebx
		lodsb
		.if	(al=09h)|(al=0Ah)|(al=0Dh)|(al=20h)
			inc	[lDstData]
			stosb
		.elseif (al=';')
			inc	[lDstData]
			stosb
			.while	(ebx)&~(al=0Ah)&~(al=0Dh)
				dec	ebx
				lodsb
				inc	[lDstData]
				stosb
			.endw
			.if	(al=0Ah)|(al=0Dh)
				.while	(ebx)&((al=0Ah)|(al=0Dh))
					dec	ebx
					lodsb
					inc	[lDstData]
					stosb
				.endw
				inc	ebx
				dec	esi
				dec	[lDstData]
				dec	edi
			.endif
		.elseif (al='!')
			inc	[lDstData]
			stosb
			.if	(ebx>sizeof_define_sample)
				push	esi
				push	edi
				mov	edi,_define_sample
				mov	ecx,sizeof_define_sample
				xor	eax,eax
				xor	edx,edx
				.while	ecx&(al=dl)
					dec	ecx
					mov	al,[esi]
					mov	dl,[edi]
					inc	esi
					inc	edi
					and	al,0DFh
					and	dl,0DFh
				.endw
				pop	edi
				pop	esi
				.if	~(ecx)&(al=dl)
					mov	ecx,sizeof_define_sample
					sub	ebx,ecx
					add	[lDstData],ecx
					rep	movsb
					.while	(ebx)&~(al=0Ah)&~(al=0Dh)
						dec	ebx
						lodsb
						inc	[lDstData]
						stosb
						.if	(ebx)&(al='`')
							dec	ebx
							lodsb
							inc	[lDstData]
							stosb
							.if	(ebx)&(al="'")
								xor	eax,eax
								xor	ecx,ecx
								push	edi
								mov	edi,string
								.while	(ebx)&~(al="'")
									dec	ebx
									lodsb
									.if	~(al="'")
										inc	ecx
										stosb
									.endif
								.endw
								pop	edi
								.if	(ecx)
									push	eax
									stdcall base64_encode,string,base64_string,ecx
									invoke	lstrlen,base64_string
									stdcall base64_encode,base64_string,base64_string_base64_string,eax
									;
									invoke	lstrlen,base64_string_base64_string
									stdcall base64_encode,base64_string_base64_string,base64_string_base64_string_base64_string,eax
									;
									invoke	lstrcpy,edi,base64_string_base64_string_base64_string
									invoke	lstrlen,base64_string_base64_string_base64_string
									add	edi,eax
									add	[lDstData],eax
									pop	eax
								.endif
								.if	(ebx)&(al="'")
									inc	[lDstData]
									stosb
									dec	ebx
									lodsb
									.if	(ebx)&(al='`')
										inc	[lDstData]
										stosb
									.else
										inc	ebx
										dec	esi
									.endif
								.endif
							.endif
						.endif
					.endw
				.endif
			.endif
		.endif
	.endw
	mov	eax,[lDstData]
	ret
  endp

  proc base64_encode uses ebx esi edi,dFrom,dTo,dSize
	mov	esi,[dFrom]
	mov	edi,[dTo]
	mov	ecx,[dSize]
	or	ecx,ecx
	jz	.r3
    .encode_loop:
	lodsd
	mov	edx,eax
	cmp	ecx,3
	jae	.remainder_ok
	and	edx,0ffffh
	cmp	ecx,2
	jae	.remainder_ok
	and	edx,0ffh
    .remainder_ok:
	bswap	edx
	mov	eax,edx
	shr	eax,26
	and	eax,00111111b
	mov	al,[base64_table+eax]
	stosb
	mov	eax,edx
	shr	eax,20
	and	eax,00111111b
	mov	al,[base64_table+eax]
	stosb
	dec	ecx
	jz	.r2
	mov	eax,edx
	shr	eax,14
	and	eax,00111111b
	mov	al,[base64_table+eax]
	stosb
	dec	ecx
	jz	.r1
	mov	eax,edx
	shr	eax,8
	and	eax,00111111b
	mov	al,[base64_table+eax]
	stosb
	dec	esi
	dec	ecx
	jnz	.encode_loop
	jmp	.r3
    .r2:
	mov	al,'='
	stosb
    .r1:
	mov	al,'='
	stosb
    .r3:
	xor	eax,eax
	stosb
	ret
  endp

section '.data' data readable writeable

  _source_file_path db '..\settings.txt',0
  _output_file_path db '..\settings.nsh',0

  align 4

  _define_sample db 'define'
  sizeof_define_sample = $ - _define_sample

  align 4

  base64_table db "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
	       db "abcdefghijklmnopqrstuvwxyz"
	       db "0123456789+/"
 
  decode_table rb 100h

  string rb 2000h
  base64_string rb 3000h
  base64_string_base64_string rb 3000h
  base64_string_base64_string_base64_string rb 3000h

section '.idata' import data readable writeable

  library kernel32,'KERNEL32.DLL',\
	  user32,'USER32.DLL'

  include 'api\kernel32.inc'
  include 'api\user32.inc'
