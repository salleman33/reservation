archives: archivezip archivetar 

archivetar:
	git archive master  --prefix=reservation/  --output=reservation.tar

archivezip:
	git archive master  --format zip --prefix=reservation/  --output=reservation.zip

 
